<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('galika_canonical_events',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('user_id')->index();$t->string('aggregate_type');$t->unsignedBigInteger('aggregate_id')->nullable()->index();$t->string('event_type')->index();$t->uuid('correlation_id')->index();$t->json('payload');$t->timestampTz('occurred_at')->useCurrent()->index();$t->timestampsTz();});
  Schema::create('galika_outbox',function(Blueprint $t){$t->bigIncrements('id');$t->uuid('event_id')->unique();$t->unsignedBigInteger('user_id')->index();$t->string('destination')->index();$t->string('kind')->index();$t->json('payload');$t->string('status')->default('PENDING')->index();$t->unsignedInteger('attempts')->default(0);$t->timestampTz('available_at')->nullable()->index();$t->timestampTz('leased_until')->nullable()->index();$t->text('last_error')->nullable();$t->timestampsTz();});
 }
 public function down():void{Schema::dropIfExists('galika_outbox');Schema::dropIfExists('galika_canonical_events');}
};