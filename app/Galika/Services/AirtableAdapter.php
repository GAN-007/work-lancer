<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AirtableAdapter{
    private function token():string{$t=config('services.airtable.token');if(!$t)throw new RuntimeException('AIRTABLE_TOKEN missing');return $t;}
    public function upsert(string $table,string $mergeField,array $fields):array{$base=config('services.airtable.base_id');if(!$base)throw new RuntimeException('AIRTABLE_BASE_ID missing');$r=Http::withToken($this->token())->timeout(30)->patch("https://api.airtable.com/v0/{$base}/".rawurlencode($table),['performUpsert'=>['fieldsToMergeOn'=>[$mergeField]],'records'=>[['fields'=>$fields]],'typecast'=>true]);if(!$r->successful())throw new RuntimeException('Airtable upsert failed '.$r->status().': '.$r->body());return $r->json();}
    public function list(string $table,array $params=[]):array{$base=config('services.airtable.base_id');$r=Http::withToken($this->token())->timeout(30)->get("https://api.airtable.com/v0/{$base}/".rawurlencode($table),$params);if(!$r->successful())throw new RuntimeException('Airtable list failed '.$r->status());return $r->json();}
}
