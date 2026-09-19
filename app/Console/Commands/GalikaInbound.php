<?php
namespace App\Console\Commands;

use App\Galika\Services\RecruiterThreadService;
use App\Models\GalikaProfile;
use Illuminate\Console\Command;

class GalikaInbound extends Command
{
    protected $signature='galika:inbound';
    protected $description='Scan connected mailboxes for recruiter messages and application outcomes';

    public function handle(RecruiterThreadService $threads):int
    {
        $n=0;
        GalikaProfile::where('pause_all_execution',false)->each(function($p)use($threads,&$n){$n+=$threads->scan($p->user_id);});
        $this->info("Processed {$n} recruiter/application messages");
        return self::SUCCESS;
    }
}
