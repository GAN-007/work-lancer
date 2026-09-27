<?php
namespace App\Console\Commands;

use App\Galika\Services\OutboxService;
use App\Models\GalikaOutboxDeadLetter;
use Illuminate\Console\Command;

class GalikaReplayDeadLetter extends Command
{
    protected $signature='galika:replay-dead-letter {id}';
    protected $description='Replay one dead-lettered asynchronous side effect with idempotent outbox semantics';

    public function handle(OutboxService $outbox): int
    {
        $dead=GalikaOutboxDeadLetter::findOrFail((int)$this->argument('id'));
        $id=$outbox->replayDeadLetter($dead->id);
        $this->info("replayed_outbox_id={$id}");
        return self::SUCCESS;
    }
}
