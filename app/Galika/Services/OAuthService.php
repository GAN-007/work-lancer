<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OAuthService{
    public function __construct(private ConnectionVault $vault){}
    public function authorizationUrl(int $userId,string $provider):string{
        $cfg=config("services.oauth.{$provider}"); if(!$cfg)throw new RuntimeException("OAuth provider {$provider} is not configured");
        $state=base64_encode(json_encode(['u'=>$userId,'p'=>$provider,'n'=>(string)Str::uuid(),'t'=>time()]));
        session(["oauth_state_{$provider}"=>$state]);
        return $cfg['authorize_url'].'?'.http_build_query([
            'client_id'=>$cfg['client_id'],'redirect_uri'=>$cfg['redirect_uri'],'response_type'=>'code',
            'scope'=>implode(' ',$cfg['scopes']??[]),'access_type'=>'offline','prompt'=>'consent','state'=>$state,
        ]);
    }
    public function exchange(int $userId,string $provider,string $code,string $state):void{
        $expected=session("oauth_state_{$provider}"); if(!$expected||!hash_equals($expected,$state))throw new RuntimeException('Invalid OAuth state');
        $cfg=config("services.oauth.{$provider}");
        $r=Http::asForm()->timeout(30)->post($cfg['token_url'],[
            'code'=>$code,'client_id'=>$cfg['client_id'],'client_secret'=>$cfg['client_secret'],
            'redirect_uri'=>$cfg['redirect_uri'],'grant_type'=>'authorization_code'
        ]);
        if(!$r->successful())throw new RuntimeException("OAuth token exchange failed for {$provider}: ".$r->body());
        $j=$r->json();$this->vault->store($userId,$provider,'oauth',[
            'access_token'=>$j['access_token']??null,'refresh_token'=>$j['refresh_token']??null,
            'expires_at'=>isset($j['expires_in'])?now()->addSeconds((int)$j['expires_in']):null,'scopes'=>preg_split('/\s+/',trim($j['scope']??''))?:[]
        ]);
        session()->forget("oauth_state_{$provider}");
    }
}
