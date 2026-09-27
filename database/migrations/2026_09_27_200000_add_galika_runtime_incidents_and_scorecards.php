<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('galika_runtime_incidents')) {
            Schema::create('galika_runtime_incidents', function (Blueprint $t) {
                $t->id();
                $t->string('fingerprint', 64)->unique();
                $t->string('severity')->default('ERROR')->index();
                $t->string('component')->index();
                $t->string('code')->index();
                $t->text('message');
                $t->json('context')->nullable();
                $t->string('state')->default('OPEN')->index();
                $t->timestampTz('first_seen_at')->index();
                $t->timestampTz('last_seen_at')->index();
                $t->timestampTz('resolved_at')->nullable();
                $t->timestampsTz();
            });
        }

        if (!Schema::hasTable('galika_scorecards')) {
            Schema::create('galika_scorecards', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable()->index();
                $t->date('period_start')->index();
                $t->date('period_end')->index();
                $t->decimal('revenue_invoiced', 16, 2)->default(0);
                $t->decimal('cash_collected', 16, 2)->default(0);
                $t->decimal('mrr', 16, 2)->default(0);
                $t->unsignedInteger('qualified_leads')->default(0);
                $t->unsignedInteger('outreach_sent')->default(0);
                $t->unsignedInteger('followups_sent')->default(0);
                $t->unsignedInteger('substantive_replies')->default(0);
                $t->unsignedInteger('meetings')->default(0);
                $t->unsignedInteger('proposals')->default(0);
                $t->unsignedInteger('wins')->default(0);
                $t->unsignedInteger('customers')->default(0);
                $t->unsignedInteger('sellable_assets_shipped')->default(0);
                $t->json('evidence')->nullable();
                $t->text('notes')->nullable();
                $t->timestampsTz();
                $t->unique(['user_id','period_start','period_end']);
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('galika_scorecards');
        Schema::dropIfExists('galika_runtime_incidents');
    }
};
