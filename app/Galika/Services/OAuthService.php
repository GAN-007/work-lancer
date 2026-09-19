<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OAuthService
{
    public function __construct(private ConnectionVault $vault){}

    public function providers():array
    {
        return array_keys(array_filter(config('services.oauth',[]),fn($v)=>is_array($v)&&!empty($v['authorize_url'])&&!empty($v['token_url'])));
    }

    public function authorizationUrl(int $userId,string $provider):string
    {
        $cfg=$this->config($provider);
        $state=base64_encode(json_encode([
            'u'=>$userId,'p'=>$provider,'n'=>(string)Str::uuid(),'t'=>time()
        ],JSON_THROW_ON_ERROR));
        session(["oauth_state_{$provider}"=>$state]);

        $params=[
            'client_id'=>$cfg['client_id'],
            'redirect_uri'=>$cfg['redirect_uri'],
            'response_type'=>'code',
            'scope'=>implode($cfg['scope_separator']??' ',$cfg['scopes']??[]),
            'state'=>$state,
        ];
        foreach($cfg['authorize_params']??[] as $k=>$v)$params[$k]=$v;

        return $cfg['authorize_url'].'?'.http_build_query($params);
    }

    public function exchange(int $userId,string $provider,string $code,string $state):void
    {
        $expected=session("oauth_state_{$provider}");
        if(!$expected||!hash_equals($expected,$state))throw new RuntimeException('Invalid OAuth state');

        $cfg=$this->config($provider);
        $payload=[
            'code'=>$code,
            'client_id'=>$cfg['client_id'],
            'client_secret'=>$cfg['client_secret'],
            'redirect_uri'=>$cfg['redirect_uri'],
            'grant_type'=>'authorization_code',
        ];
        foreach($cfg['token_params']??[] as $k=>$v)$payload[$k]=$v;

        $r=Http::asForm()->timeout(30)->post($cfg['token_url'],$payload);
        if(!$r->successful())throw new RuntimeException("OAuth token exchange failed for {$provider}: ".$r->body());

        $j=$r->json();
        $this->vault->store($userId,$provider,'oauth',[
            'access_token'=>$j['access_token']??null,
            'refresh_token'=>$j['refresh_token']??null,
            'expires_at'=>isset($j['expires_in'])?now()->addSeconds((int)$j['expires_in']):null,
            'scopes'=>$this->normalizeScopes($j['scope']??($cfg['scopes']??[])),
        ],['account_label'=>$j['email']??null]);

        session()->forget("oauth_state_{$provider}");
    }

    public function refresh(int $userId,string $provider):string
    {
        $cfg=$this->config($provider);
        $refresh=$this->vault->credential($userId,$provider,'refresh_token');

        $payload=[
            'refresh_token'=>$refresh,
            'client_id'=>$cfg['client_id'],
            'client_secret'=>$cfg['client_secret'],
            'grant_type'=>'refresh_token',
        ];
        foreach($cfg['refresh_params']??[] as $k=>$v)$payload[$k]=$v;

        $r=Http::asForm()->timeout(30)->post($cfg['token_url'],$payload);
        if(!$r->successful())throw new RuntimeException("OAuth refresh failed for {$provider}: ".$r->body());

        $j=$r->json();
        $this->vault->store($userId,$provider,'oauth',[
            'access_token'=>$j['access_token']??null,
            'refresh_token'=>$j['refresh_token']??$refresh,
            'expires_at'=>isset($j['expires_in'])?now()->addSeconds((int)$j['expires_in']):null,
            'scopes'=>$this->normalizeScopes($j['scope']??($cfg['scopes']??[])),
        ]);

        return $this->vault->credential($userId,$provider,'access_token');
    }

    private function config(string $provider):array
    {
        $cfg=config("services.oauth.{$provider}");
        if(!$cfg||empty($cfg['client_id'])||empty($cfg['client_secret']))throw new RuntimeException("OAuth provider {$provider} is not configured");
        return $cfg;
    }

    private function normalizeScopes(array|string $scopes):array
    {
        if(is_array($scopes))return array_values(array_filter($scopes));
        return array_values(array_filter(preg_split('/[\s,]+/',trim($scopes))?:[]));
    }
}
