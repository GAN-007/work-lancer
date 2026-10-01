<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class ConnectionHealthService
{
    public function __construct(
        private ConnectionVault $vault,
        private OAuthService $oauth,
        private OpenWebAgentAdapter $webAgent,
        private BaserowAdapter $baserow
    ){}

    public function test(int $userId,string $provider):array
    {
        try{
            if($provider==='open_web_agent'){
                return $this->webAgent->health();
            }

            if($provider==='baserow'){
                return $this->baserow->health();
            }

            if($provider==='openai'){
                $token=$this->vault->credential($userId,'openai','api_key');
                $r=Http::withToken($token)->timeout(20)->get('https://api.openai.com/v1/models');
            }elseif(in_array($provider,['gmail','linkedin','lever'],true)){
                if($this->vault->expiring($userId,$provider)) $token=$this->oauth->refresh($userId,$provider);
                else $token=$this->vault->credential($userId,$provider,'access_token');

                $url=match($provider){
                    'gmail'=>'https://gmail.googleapis.com/gmail/v1/users/me/profile',
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
            if(in_array($provider,['openai','gmail','linkedin','lever'],true)){
                $this->vault->markHealth($userId,$provider,false,$e->getMessage());
            }
            return ['ok'=>false,'error'=>$e->getMessage()];
        }
    }
}
