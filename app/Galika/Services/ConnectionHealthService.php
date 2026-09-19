<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class ConnectionHealthService
{
    public function __construct(private ConnectionVault $vault,private OAuthService $oauth){}

    public function test(int $userId,string $provider):array
    {
        try{
            if($provider==='openai'){
                $token=$this->vault->credential($userId,'openai','api_key');
                $r=Http::withToken($token)->timeout(20)->get('https://api.openai.com/v1/models');
            }elseif($provider==='tinyfish'){
                $token=$this->vault->credential($userId,'tinyfish','api_key');
                $endpoint=config('services.tinyfish.health_endpoint')?:config('services.tinyfish.endpoint');
                $r=Http::withToken($token)->timeout(20)->get($endpoint);
            }elseif(in_array($provider,['gmail','airtable','linkedin','lever'],true)){
                if($this->vault->expiring($userId,$provider)) $token=$this->oauth->refresh($userId,$provider);
                else $token=$this->vault->credential($userId,$provider,'access_token');

                $url=match($provider){
                    'gmail'=>'https://gmail.googleapis.com/gmail/v1/users/me/profile',
                    'airtable'=>'https://api.airtable.com/v0/meta/bases',
                    'linkedin'=>'https://api.linkedin.com/v2/userinfo',
                    'lever'=>'https://api.lever.co/v1/opportunities?limit=1',
                };
                $r=Http::withToken($token)->timeout(20)->get($url);
            }else{
                return ['ok'=>false,'error'=>'No health check for provider'];
            }

            $ok=$r->successful();
            $this->vault->markHealth($userId,$provider,$ok,$ok?null:$r->body());
            return ['ok'=>$ok,'status'=>$r->status()];
        }catch(Throwable $e){
            $this->vault->markHealth($userId,$provider,false,$e->getMessage());
            return ['ok'=>false,'error'=>$e->getMessage()];
        }
    }
}
