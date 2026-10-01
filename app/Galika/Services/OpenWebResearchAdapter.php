<?php
namespace App\Galika\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenWebResearchAdapter
{
    public function searchJobs(string $query,int $limit=20):array
    {
        $response=$this->request()->post($this->endpoint('/v1/search'),[
            'query'=>$query.' jobs careers hiring',
            'page'=>1,
            'language'=>'all',
            'safe_search'=>1,
            'limit'=>max(1,min(100,$limit)),
        ]);

        if(!$response->successful()){
            throw new RuntimeException('Open Web search failed '.$response->status().': '.$response->body());
        }

        return $response->json('results',[])?:[];
    }

    public function fetchPage(string $url):array
    {
        $response=$this->request(120)->post($this->endpoint('/v1/fetch'),[
            'url'=>$url,
            'word_count_threshold'=>1,
            'max_chars'=>200000,
        ]);

        if(!$response->successful()){
            throw new RuntimeException('Open Web fetch failed '.$response->status().': '.$response->body());
        }

        return $response->json()?:[];
    }

    private function request(int $timeout=45):PendingRequest
    {
        $request=Http::acceptJson()->asJson()->timeout($timeout)->retry(2,250);
        $token=(string)config('services.open_web_agent.token');
        return $token!==''?$request->withToken($token):$request;
    }

    private function endpoint(string $path):string
    {
        $base=rtrim((string)config('services.open_web_agent.endpoint'),'/');
        if($base==='')throw new RuntimeException('Open Web Agent endpoint missing');
        return $base.$path;
    }
}
