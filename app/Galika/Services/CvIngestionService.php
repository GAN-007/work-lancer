<?php
namespace App\Galika\Services;

use App\Models\GalikaDocument;
use App\Models\GalikaEvidence;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CvIngestionService
{
    public function __construct(private GalikaAIService $ai){}

    public function ingest(int $userId,UploadedFile $file):GalikaDocument
    {
        $mime=$file->getMimeType();
        if(!in_array($mime,['application/pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document','text/plain'],true)) {
            throw new RuntimeException('Unsupported CV format');
        }

        $bytes=file_get_contents($file->getRealPath());
        $sha=hash('sha256',$bytes);
        $path=$file->storeAs("galika/{$userId}/documents",$sha.'-'.$file->getClientOriginalName(),'local');
        $text=$this->extract($file->getRealPath(),$mime);
        $facts=$this->ai->extractEvidence($text);

        return GalikaDocument::create([
            'user_id'=>$userId,'kind'=>'CV','disk'=>'local','path'=>$path,
            'original_name'=>$file->getClientOriginalName(),'mime'=>$mime,'size'=>$file->getSize(),
            'sha256'=>$sha,'extracted_text'=>$text,'extracted_facts'=>$facts,'confirmed'=>false
        ]);
    }

    public function confirm(GalikaDocument $doc,array $acceptedIndexes):void
    {
        foreach($doc->extracted_facts??[] as $i=>$fact){
            if(!in_array($i,$acceptedIndexes,true)) continue;
            GalikaEvidence::updateOrCreate(
                ['evidence_key'=>hash('sha256',$doc->user_id.'|'.$doc->id.'|'.$i)],
                [
                    'user_id'=>$doc->user_id,'domain'=>$fact['domain']??'GENERAL','fact'=>$fact['fact'],
                    'source_type'=>'CV','source_url'=>null,'source_ref'=>(string)$doc->id,
                    'confidence'=>$fact['confidence']??0.9,'verified'=>true,'tags'=>$fact['tags']??[]
                ]
            );
        }
        $doc->update(['confirmed'=>true]);
    }

    private function extract(string $path,string $mime):string
    {
        if($mime==='text/plain') return file_get_contents($path);

        if($mime==='application/pdf'){
            $cmd='pdftotext '.escapeshellarg($path).' -';
            exec($cmd,$out,$code);
            if($code===0) return trim(implode("\n",$out));
        }

        if($mime==='application/vnd.openxmlformats-officedocument.wordprocessingml.document'){
            $zip=new \ZipArchive();
            if($zip->open($path)===true){
                $xml=$zip->getFromName('word/document.xml');
                $zip->close();
                if($xml!==false){
                    return trim(html_entity_decode(strip_tags(str_replace(['</w:p>','</w:tr>'],["\n","\n"],$xml))));
                }
            }
        }

        throw new RuntimeException('Unable to extract CV text. Ensure pdftotext is installed for PDFs.');
    }
}
