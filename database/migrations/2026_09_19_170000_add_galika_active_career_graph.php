<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up():void{
  Schema::create('galika_source_metrics',function(Blueprint $t){$t->id();$t->string('source')->unique();$t->unsignedBigInteger('discovered')->default(0);$t->unsignedBigInteger('submitted')->default(0);$t->unsignedBigInteger('responses')->default(0);$t->unsignedBigInteger('interviews')->default(0);$t->unsignedBigInteger('offers')->default(0);$t->decimal('yield_score',10,4)->default(0);$t->timestamp('last_seen_at')->nullable();$t->timestamps();});
  Schema::create('galika_campaign_actions',function(Blueprint $t){$t->id();$t->foreignId('campaign_id')->constrained('galika_campaigns')->cascadeOnDelete();$t->string('action');$t->string('state')->default('PENDING');$t->foreignId('person_id')->nullable()->constrained('galika_people')->nullOnDelete();$t->string('channel')->nullable();$t->text('payload')->nullable();$t->timestamp('scheduled_at')->nullable();$t->timestamp('executed_at')->nullable();$t->json('proof')->nullable();$t->text('error')->nullable();$t->timestamps();$t->index(['state','scheduled_at']);});
  Schema::create('galika_next_actions',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('subject_type');$t->unsignedBigInteger('subject_id');$t->string('action');$t->string('priority')->default('NORMAL');$t->string('state')->default('PENDING');$t->json('reason')->nullable();$t->timestamp('due_at')->nullable();$t->timestamp('completed_at')->nullable();$t->timestamps();$t->index(['user_id','state','due_at']);});
  Schema::create('galika_human_assists',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->foreignId('application_id')->nullable()->constrained('galika_applications')->nullOnDelete();$t->string('kind');$t->string('state')->default('OPEN');$t->text('instructions')->nullable();$t->json('context')->nullable();$t->timestamp('resolved_at')->nullable();$t->timestamps();});
  Schema::create('galika_document_variants',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->foreignId('persona_id')->nullable()->constrained('galika_personas')->nullOnDelete();$t->string('kind');$t->string('variant_key');$t->foreignId('document_id')->constrained('galika_documents')->cascadeOnDelete();$t->unsignedBigInteger('applications')->default(0);$t->unsignedBigInteger('responses')->default(0);$t->unsignedBigInteger('interviews')->default(0);$t->unsignedBigInteger('offers')->default(0);$t->decimal('score',10,4)->default(0);$t->timestamps();$t->unique(['user_id','variant_key','kind']);});
  Schema::table('galika_profiles',function(Blueprint $t){$t->unsignedInteger('max_interviews_per_week')->default(5);$t->json('preferred_interview_hours')->nullable();$t->boolean('rejection_digest')->default(true);});
  Schema::table('galika_applications',function(Blueprint $t){$t->unsignedInteger('follow_up_touches')->default(0);$t->string('source')->nullable()->index();$t->string('conditional_state')->nullable();});
 }
 public function down():void{
  Schema::table('galika_applications',fn(Blueprint $t)=>$t->dropColumn(['follow_up_touches','source','conditional_state']));
  Schema::table('galika_profiles',fn(Blueprint $t)=>$t->dropColumn(['max_interviews_per_week','preferred_interview_hours','rejection_digest']));
  foreach(['galika_document_variants','galika_human_assists','galika_next_actions','galika_campaign_actions','galika_source_metrics'] as $x)Schema::dropIfExists($x);
 }
};