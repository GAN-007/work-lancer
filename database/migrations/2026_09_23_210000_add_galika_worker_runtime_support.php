<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up():void{
  if(!Schema::hasTable('galika_replica_checkpoints')) Schema::create('galika_replica_checkpoints',function(Blueprint $t){$t->id();$t->string('sink');$t->unsignedBigInteger('user_id')->nullable()->index();$t->unsignedBigInteger('last_outbox_id')->default(0);$t->timestampTz('last_success_at')->nullable();$t->text('last_error')->nullable();$t->timestampsTz();$t->unique(['sink','user_id']);});
  if(!Schema::hasTable('galika_worker_heartbeats')) Schema::create('galika_worker_heartbeats',function(Blueprint $t){$t->id();$t->string('worker')->unique();$t->string('instance_id');$t->timestampTz('heartbeat_at')->index();$t->json('meta')->nullable();$t->timestampsTz();});
 }
 public function down():void{Schema::dropIfExists('galika_worker_heartbeats');Schema::dropIfExists('galika_replica_checkpoints');}
};