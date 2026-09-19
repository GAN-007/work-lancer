<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AirtableAdapter
{
    public function __construct(private ConnectionVault $vault,private OAuthService $oauth){}

    private function token(int $userId):string
    {
        try{
            if($this->vault->expiring($userId,'airtable')) return $this->oauth->refresh($userId,'airtable');
            return $this->vault->credential($userId,'airtable','access_token');
        }catch(\Throwable $e){
            $legacy=config('services.airtable.token');
            if(!$legacy) throw new RuntimeException('Airtable connection missing');
            return $legacy;
        }
    }

    public function bases(int $userId):array
    {
        $r=Http::withToken($this->token($userId))->timeout(30)->get('https://api.airtable.com/v0/meta/bases');
        if(!$r->successful()) throw new RuntimeException('Airtable base discovery failed '.$r->status().': '.$r->body());
        return $r->json('bases')??[];
    }

    public function selectedBase(int $userId):string
    {
        $connection=$this->vault->connection($userId,'airtable');
        $base=data_get($connection->metadata,'base_id');
        if(!$base) $base=config('services.airtable.base_id');
        if(!$base) throw new RuntimeException('No Airtable base selected for this user');
        return $base;
    }

    public function selectBase(int $userId,string $baseId,string $baseName):void
    {
        $connection=$this->vault->connection($userId,'airtable');
        $meta=$connection->metadata??[];
        $meta['base_id']=$baseId;
        $meta['base_name']=$baseName;
        $connection->update(['metadata'=>$meta]);
    }

    public function upsert(int $userId,string $table,string $mergeField,array $fields):array
    {
        $base=$this->selectedBase($userId);

        $r=Http::withToken($this->token($userId))->timeout(30)
            ->patch("https://api.airtable.com/v0/{$base}/".rawurlencode($table),[
                'performUpsert'=>['fieldsToMergeOn'=>[$mergeField]],
                'records'=>[['fields'=>$fields]],
                'typecast'=>true
            ]);
        if(!$r->successful()) throw new RuntimeException('Airtable upsert failed '.$r->status().': '.$r->body());
        return $r->json();
    }

    public function list(int $userId,string $table,array $params=[]):array
    {
        $base=$this->selectedBase($userId);

        $r=Http::withToken($this->token($userId))->timeout(30)
            ->get("https://api.airtable.com/v0/{$base}/".rawurlencode($table),$params);
        if(!$r->successful()) throw new RuntimeException('Airtable list failed '.$r->status().': '.$r->body());
        return $r->json();
    }
}
