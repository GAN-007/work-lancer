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
        if(str_contains($url,'ashbyhq.com')) return 'ASHBY';
        if(str_contains($url,'myworkdayjobs.com')||str_contains($url,'workday.com')) return 'WORKDAY';
        if(str_contains($url,'linkedin.com')) return 'LINKEDIN';
        if(str_contains($url,'solvo.global')||str_contains($url,'solvo')) return 'SOLVO';
        if(str_contains($url,'smartrecruiters.com')) return 'SMARTRECRUITERS';
        if(str_contains($url,'jobvite.com')) return 'JOBVITE';
        if(str_contains($url,'icims.com')) return 'ICIMS';
        if(str_contains($url,'successfactors')) return 'SUCCESSFACTORS';
        if(str_contains($url,'ceipal.com')||str_contains($url,'ceipaljobs.com')) return 'CEIPAL';
        if(str_contains($url,'next.co')||str_contains($url,'next-jobs')) return 'NEXT';
        return 'EMPLOYER_FORM';
    }
    public function browserFirstClass(string $type):bool{return in_array($type,['GREENHOUSE','LEVER','ASHBY','WORKDAY','LINKEDIN','CEIPAL','SOLVO','SMARTRECRUITERS','JOBVITE','ICIMS','SUCCESSFACTORS','NEXT','EMPLOYER_FORM'],true);}
}
