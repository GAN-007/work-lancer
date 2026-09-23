<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  if(!Schema::hasTable('galika_execution_work_items')) Schema::create('galika_execution_work_items',function(Blueprint $t){
   $t->id();$t->uuid('correlation_id')->index();$t->string('kind',64)->index();$t->json('payload');$t->string('idempotency_key')->unique();$t->string('status',24)->default('QUEUED')->index();$t->unsignedInteger('attempts')->default(0);$t->unsignedInteger('max_attempts')->default(20);$t->timestamp('available_at')->nullable()->index();$t->timestamp('leased_until')->nullable()->index();$t->text('last_error')->nullable();$t->timestamps();
  });
  Schema::create('galika_outbox',function(Blueprint $t){
   $t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('destination',64)->index();$t->string('operation',64)->index();$t->string('idempotency_key')->unique();$t->json('payload');$t->string('status',24)->default('PENDING')->index();$t->unsignedInteger('attempts')->default(0);$t->unsignedInteger('max_attempts')->default(25);$t->timestamp('available_at')->nullable()->index();$t->timestamp('leased_until')->nullable();$t->text('last_error')->nullable();$t->timestamp('delivered_at')->nullable();$t->timestamps();
  });
  Schema::create('galika_replica_cursors',function(Blueprint $t){$t->id();$t->string('replica')->unique();$t->unsignedBigInteger('last_event_id')->default(0);$t->timestamp('last_success_at')->nullable();$t->text('last_error')->nullable();$t->timestamps();});
  Schema::create('galika_worker_heartbeats',function(Blueprint $t){$t->id();$t->string('worker')->unique();$t->uuid('instance_id');$t->string('state')->default('ALIVE');$t->timestamp('heartbeat_at')->index();$t->json('metadata')->nullable();$t->timestamps();});
  Schema::create('galika_submission_evidence',function(Blueprint $t){$t->id();$t->foreignId('application_id')->constrained('galika_applications')->cascadeOnDelete();$t->string('kind');$t->string('provider')->nullable();$t->string('external_id')->nullable();$t->text('value')->nullable();$t->json('metadata')->nullable();$t->timestamp('observed_at');$t->timestamps();$t->index(['application_id','kind']);});
 }
 public function down():void{foreach(['galika_submission_evidence','galika_worker_heartbeats','galika_replica_cursors','galika_outbox','galika_execution_work_items'] as $x)Schema::dropIfExists($x);}
};