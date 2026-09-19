<?php
namespace App\Galika\Services;

use App\Models\GalikaDocument;
use App\Models\GalikaEvidence;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Storage;

class DocumentGenerationService
{
    public function __construct(private GalikaAIService $ai){}

    public function generateForOpportunity(int $userId,GalikaOpportunity $o):array
    {
        $evidence=GalikaEvidence::where('user_id',$userId)->where('verified',true)->get()->toArray();
        $pack=$this->ai->generateApplicationPack($evidence,$o->toArray());
        $result=[];

        foreach(['resume'=>'RESUME','cover_letter'=>'COVER_LETTER'] as $key=>$kind){
            $content=$pack[$key];
            $sha=hash('sha256',$content);
            $path="galika/{$userId}/generated/{$sha}.txt";
            Storage::disk('local')->put($path,$content);
            $result[$key]=GalikaDocument::create([
                'user_id'=>$userId,'kind'=>$kind,'disk'=>'local','path'=>$path,
                'original_name'=>strtolower($kind).'-'.$o->id.'.txt','mime'=>'text/plain',
                'size'=>strlen($content),'sha256'=>$sha,'extracted_text'=>$content,
                'extracted_facts'=>[],'confirmed'=>true
            ]);
        }
        return $result;
    }
}
