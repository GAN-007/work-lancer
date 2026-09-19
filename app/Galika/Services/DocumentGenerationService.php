<?php
namespace App\Galika\Services;

use App\Models\GalikaDocument;
use App\Models\GalikaDocumentVariant;
use App\Models\GalikaEvidence;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Storage;

class DocumentGenerationService
{
    public function __construct(private GalikaIntelligenceService $ai,private PersonaSelectionService $personas){}

    public function generateForOpportunity(int $userId,GalikaOpportunity $o):array
    {
        $evidence=GalikaEvidence::where('user_id',$userId)->where('verified',true)->get()->toArray();
        $persona=$this->personas->select($userId,$o);
        $context=$o->toArray()+['persona'=>$persona?->toArray()];
        $pack=$this->ai->applicationPack($userId,$evidence,$context);
        $result=[];

        foreach(['resume'=>'RESUME','cover_letter'=>'COVER_LETTER'] as $key=>$kind){
            $content=$pack[$key];
            $sha=hash('sha256',$content);
            $txtPath="galika/{$userId}/generated/{$sha}.txt";
            Storage::disk('local')->put($txtPath,$content);
            $doc=GalikaDocument::create([
                'user_id'=>$userId,'kind'=>$kind,'disk'=>'local','path'=>$txtPath,
                'original_name'=>strtolower($kind).'-'.$o->id.'.txt','mime'=>'text/plain',
                'size'=>strlen($content),'sha256'=>$sha,'extracted_text'=>$content,
                'extracted_facts'=>[],'confirmed'=>true
            ]);
            $variantKey=hash('sha256',$userId.'|'.($persona?->id??0).'|'.$kind.'|'.$o->title);
            GalikaDocumentVariant::updateOrCreate(
                ['user_id'=>$userId,'variant_key'=>$variantKey,'kind'=>$kind],
                ['persona_id'=>$persona?->id,'document_id'=>$doc->id]
            );
            $result[$key]=$doc;
        }
        return $result;
    }
}
