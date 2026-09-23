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
        if(str_contains($url,'ashbyhq.com')) return 'ASHBY';
        if(str_contains($url,'smartrecruiters.com')) return 'SMARTRECRUITERS';
        if(str_contains($url,'workable.com')||str_contains($url,'apply.workable.com')) return 'WORKABLE';
        if(str_contains($url,'manatal.com')) return 'MANATAL';
        if(str_contains($url,'ceipal.com')||str_contains($url,'ceipaljobs.com')) return 'CEIPAL';
        if(str_contains($url,'talenthr.io')) return 'TALENTHR';
        if(str_contains($url,'linkedin.com')) return 'LINKEDIN';
        return 'GENERIC';
    }
}
