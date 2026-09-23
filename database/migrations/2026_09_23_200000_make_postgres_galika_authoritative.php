<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('galika_outbox',function(Blueprint $t){$t->id();$t->uuid('event_id')->unique();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('topic')->index();$t->json('payload');$t->string('status')->default('PENDING')->index();$t->unsignedInteger('attempts')->default(0);$t->timestamp('available_at')->nullable()->index();$t->timestamp('leased_until')->nullable()->index();$t->text('last_error')->nullable();$t->timestamps();});
  Schema::create('galika_event_ledger',function(Blueprint $t){$t->id();$t->uuid('event_id')->unique();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('aggregate_type')->index();$t->unsignedBigInteger('aggregate_id')->nullable()->index();$t->string('event_type')->index();$t->json('payload')->nullable();$t->timestamp('occurred_at')->index();$t->timestamps();});
  Schema::create('galika_worker_heartbeats',function(Blueprint $t){$t->id();$t->string('worker')->unique();$t->string('instance_id');$t->string('state')->default('RUNNING');$t->timestamp('heartbeat_at')->index();$t->json('metadata')->nullable();$t->timestamps();});
 }
 public function down():void {Schema::dropIfExists('galika_worker_heartbeats');Schema::dropIfExists('galika_event_ledger');Schema::dropIfExists('galika_outbox');}
};