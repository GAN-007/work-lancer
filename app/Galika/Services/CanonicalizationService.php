<?php
namespace App\Galika\Services;

use App\Models\GalikaOpportunity;
use Illuminate\Support\Str;

class CanonicalizationService{
    public function fingerprint(array $job):string{
        $employer=Str::lower(trim((string)($job['employer']??$job['companyName']??'')));
        $title=Str::lower(trim((string)($job['title']??$job['jobTitle']??'')));
        $req=Str::lower(trim((string)($job['requisition_id']??$job['external_id']??'')));
        $loc=Str::lower(trim((string)($job['location']??$job['jobGeo']??'')));
        return hash('sha256',implode('|',[$employer,$title,$req,$loc]));
    }
    public function resolve(array $job):?GalikaOpportunity{
        $fp=$this->fingerprint($job);
        return GalikaOpportunity::where('fingerprint',$fp)->first();
    }
}
