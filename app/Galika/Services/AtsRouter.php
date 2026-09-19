<?php
namespace App\Galika\Services;

use App\Models\GalikaOpportunity;

class AtsRouter
{
    public function detect(GalikaOpportunity $o):string
    {
        $url=mb_strtolower((string)($o->official_url?:$o->url));
        if(str_contains($url,'greenhouse.io')||str_contains($url,'boards.greenhouse')) return 'GREENHOUSE';
        if(str_contains($url,'lever.co')) return 'LEVER';
        if(str_contains($url,'myworkdayjobs.com')||str_contains($url,'workday.com')) return 'WORKDAY';
        if(str_contains($url,'linkedin.com')) return 'LINKEDIN';
        return 'GENERIC';
    }
}
