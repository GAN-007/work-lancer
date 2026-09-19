<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class ConnectionHealthService{
    public function __construct(private ConnectionVault $vault){}
    public function test(int $userId,string $provider):array{
        try{
            if($provider==='openai'){
                $key=$this->vault->credential($userId,'openai','api_key');
                $r=Http::withToken($key)->timeout(20)->get('https://api.openai.com/v1/models');$ok=$r->successful();
            }elseif($provider==='airtable'){
                $key=$this->vault->credential($userId,'airtable','api_key');
                $r=Http::withToken($key)->timeout(20)->get('https://api.airtable.com/v0/meta/bases');$ok=$r->successful();
            }elseif($provider==='tinyfish'){
                $key=$this->vault->credential($userId,'tinyfish','api_key');
                $endpoint=config('services.tinyfish.health_endpoint')?:config('services.tinyfish.endpoint');
                $r=Http::withToken($key)->timeout(20)->get($endpoint);$ok=$r->successful();
            }elseif($provider==='gmail'){
                $token=$this->vault->credential($userId,'gmail','access_token');
                $r=Http::withToken($token)->timeout(20)->get('https://gmail.googleapis.com/gmail/v1/users/me/profile');$ok=$r->successful();
            }else{
                return ['ok'=>false,'error'=>'No health check for provider'];
            }
            $this->vault->markHealth($userId,$provider,$ok,$ok?null:$r->body());
            return ['ok'=>$ok,'status'=>$r->status(),'body'=>$r->json()??$r->body()];
        }catch(Throwable $e){$this->vault->markHealth($userId,$provider,false,$e->getMessage());return ['ok'=>false,'error'=>$e->getMessage()];}
    }
}
