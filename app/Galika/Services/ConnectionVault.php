<?php
namespace App\Galika\Services;

use App\Models\GalikaConnection;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class ConnectionVault{
    public function store(int $userId,string $provider,string $authType,array $credentials,array $meta=[]):GalikaConnection{
        $row=GalikaConnection::updateOrCreate(
            ['user_id'=>$userId,'provider'=>$provider],
            [
                'auth_type'=>$authType,
                'account_label'=>$meta['account_label']??null,
                'access_token'=>isset($credentials['access_token'])?Crypt::encryptString($credentials['access_token']):null,
                'refresh_token'=>isset($credentials['refresh_token'])?Crypt::encryptString($credentials['refresh_token']):null,
                'api_key'=>isset($credentials['api_key'])?Crypt::encryptString($credentials['api_key']):null,
                'expires_at'=>$credentials['expires_at']??null,
                'scopes'=>$credentials['scopes']??[],
                'metadata'=>$meta,
                'health'=>'UNKNOWN',
                'last_error'=>null,
            ]
        );
        return $row;
    }
    public function credential(int $userId,string $provider,string $field):string{
        $row=GalikaConnection::where(['user_id'=>$userId,'provider'=>$provider])->firstOrFail();
        $raw=$row->{$field};
        if(!$raw)throw new RuntimeException("Missing {$field} for {$provider}");
        return Crypt::decryptString($raw);
    }
    public function connection(int $userId,string $provider):GalikaConnection
    {
        return GalikaConnection::where(['user_id'=>$userId,'provider'=>$provider])->firstOrFail();
    }

    public function expiring(int $userId,string $provider,int $withinSeconds=120):bool
    {
        $row=$this->connection($userId,$provider);
        return $row->expires_at? $row->expires_at->lte(now()->addSeconds($withinSeconds)) : false;
    }

    public function markHealth(int $userId,string $provider,bool $ok,?string $error=null):void{
        GalikaConnection::where(['user_id'=>$userId,'provider'=>$provider])->update(['health'=>$ok?'HEALTHY':'UNHEALTHY','last_error'=>$error,'last_health_at'=>now()]);
    }
}
