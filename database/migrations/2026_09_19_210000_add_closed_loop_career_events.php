<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up():void{
  Schema::create('galika_career_events',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('subject_type');$t->unsignedBigInteger('subject_id');$t->string('event_type');$t->string('state')->nullable();$t->string('proof_level')->default('INTERNAL');$t->json('evidence')->nullable();$t->timestamp('occurred_at')->nullable();$t->timestamps();$t->index(['user_id','subject_type','subject_id']);});
  Schema::create('galika_role_lineages',function(Blueprint $t){$t->id();$t->string('lineage_key')->index();$t->foreignId('opportunity_id')->constrained('galika_opportunities')->cascadeOnDelete();$t->string('version_hash');$t->string('state')->default('ACTIVE');$t->json('snapshot')->nullable();$t->timestamp('observed_at')->nullable();$t->timestamps();$t->unique(['opportunity_id','version_hash']);});
  Schema::table('galika_human_assists',function(Blueprint $t){$t->string('resume_token')->nullable()->unique();$t->timestamp('expires_at')->nullable();$t->timestamp('resumed_at')->nullable();});
  Schema::table('galika_applications',function(Blueprint $t){$t->timestamp('withdrawn_at')->nullable();$t->foreignId('superseded_by_application_id')->nullable()->constrained('galika_applications')->nullOnDelete();});
 }
 public function down():void{
  Schema::table('galika_applications',fn(Blueprint $t)=>$t->dropConstrainedForeignId('superseded_by_application_id'));
  Schema::table('galika_applications',fn(Blueprint $t)=>$t->dropColumn('withdrawn_at'));
  Schema::table('galika_human_assists',fn(Blueprint $t)=>$t->dropColumn(['resume_token','expires_at','resumed_at']));
  Schema::dropIfExists('galika_role_lineages');Schema::dropIfExists('galika_career_events');
 }
};