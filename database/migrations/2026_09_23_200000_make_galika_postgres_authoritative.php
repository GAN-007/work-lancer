<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up():void{
  Schema::create('galika_outbox',function(Blueprint $t){$t->id();$t->uuid('event_id')->unique();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('topic')->index();$t->string('aggregate_type')->nullable();$t->unsignedBigInteger('aggregate_id')->nullable();$t->json('payload');$t->string('status')->default('PENDING')->index();$t->unsignedInteger('attempts')->default(0);$t->timestamp('available_at')->nullable()->index();$t->timestamp('leased_until')->nullable()->index();$t->timestamp('delivered_at')->nullable();$t->text('last_error')->nullable();$t->timestamps();$t->index(['aggregate_type','aggregate_id']);});
  Schema::create('galika_replica_checkpoints',function(Blueprint $t){$t->id();$t->string('sink');$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->unsignedBigInteger('last_outbox_id')->default(0);$t->timestamp('last_success_at')->nullable();$t->text('last_error')->nullable();$t->timestamps();$t->unique(['sink','user_id']);});
  Schema::create('galika_worker_heartbeats',function(Blueprint $t){$t->id();$t->string('worker')->unique();$t->string('instance_id');$t->timestamp('heartbeat_at')->index();$t->json('meta')->nullable();$t->timestamps();});
 }
 public function down():void{Schema::dropIfExists('galika_worker_heartbeats');Schema::dropIfExists('galika_replica_checkpoints');Schema::dropIfExists('galika_outbox');}
};