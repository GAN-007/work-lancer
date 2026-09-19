<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('galika_profiles', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('headline')->nullable(); $t->string('location')->nullable();
            $t->string('country')->nullable(); $t->boolean('remote_from_home_country')->default(true);
            $t->boolean('willing_to_relocate')->default(false); $t->string('timezone')->default('UTC');
            $t->unsignedInteger('minimum_match_score')->default(70);
            $t->boolean('autonomous_apply_enabled')->default(false);
            $t->boolean('pause_all_execution')->default(false);
            $t->string('review_mode')->default('MATERIAL_ONLY');
            $t->unsignedInteger('daily_application_cap')->default(40);
            $t->unsignedInteger('hourly_application_cap')->default(8);
            $t->timestamps();
        });
        Schema::create('galika_preferences', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('kind')->index(); $t->string('value')->index(); $t->json('meta')->nullable(); $t->timestamps();
            $t->unique(['user_id','kind','value']);
        });
        Schema::create('galika_evidence', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('evidence_key')->unique(); $t->string('domain')->index(); $t->longText('fact');
            $t->string('source_type'); $t->text('source_url')->nullable(); $t->string('source_ref')->nullable();
            $t->decimal('confidence',5,4)->default(1); $t->boolean('verified')->default(false);
            $t->json('tags')->nullable(); $t->timestamps();
        });
        Schema::create('galika_assets', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('kind')->index(); $t->string('label'); $t->string('storage_disk')->default('local');
            $t->string('storage_path'); $t->string('sha256',64)->index(); $t->string('mime_type')->nullable();
            $t->unsignedBigInteger('size_bytes')->nullable(); $t->boolean('active')->default(true); $t->json('meta')->nullable(); $t->timestamps();
        });
        Schema::create('galika_integrations', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider')->index(); $t->string('state')->default('DISCONNECTED');
            $t->string('credential_ref')->nullable(); $t->timestamp('last_health_check')->nullable();
            $t->timestamp('last_success')->nullable(); $t->text('last_error')->nullable(); $t->json('capabilities')->nullable();
            $t->timestamps(); $t->unique(['user_id','provider']);
        });
        Schema::create('galika_policies', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('policy_key'); $t->string('operator')->default('ALLOW'); $t->json('rule');
            $t->boolean('enabled')->default(true); $t->unsignedInteger('priority')->default(100); $t->timestamps();
            $t->unique(['user_id','policy_key']);
        });
        Schema::create('galika_email_routes', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('employer')->index(); $t->string('email')->index(); $t->string('domain')->index();
            $t->string('purpose')->nullable(); $t->string('state')->default('UNKNOWN')->index();
            $t->text('evidence_url')->nullable(); $t->longText('evidence_basis')->nullable();
            $t->timestamp('last_sent_at')->nullable(); $t->timestamp('last_reconciled_at')->nullable();
            $t->unsignedInteger('hard_bounces')->default(0); $t->boolean('do_not_retry')->default(false);
            $t->string('replacement_route')->nullable(); $t->timestamps(); $t->unique(['user_id','email']);
        });
        Schema::create('galika_delivery_events', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('application_id')->nullable()->constrained('galika_applications')->nullOnDelete();
            $t->string('event_key')->unique(); $t->string('type')->index(); $t->timestamp('event_at')->index();
            $t->string('route')->nullable(); $t->string('provider_message_id')->nullable()->index();
            $t->string('dsn_code')->nullable(); $t->longText('evidence')->nullable();
            $t->string('terminal_effect')->nullable(); $t->boolean('avoidable_defect')->default(false); $t->timestamps();
        });
        Schema::create('galika_audit_events', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->uuid('correlation_id')->nullable()->index(); $t->string('actor_type')->index(); $t->string('action')->index();
            $t->string('subject_type')->nullable(); $t->string('subject_id')->nullable();
            $t->json('before')->nullable(); $t->json('after')->nullable(); $t->json('context')->nullable(); $t->timestamps();
        });
        Schema::table('galika_opportunities', function(Blueprint $t){$t->string('fingerprint')->nullable()->after('canonical_key')->index();$t->string('requisition_id')->nullable()->index();$t->string('source_authority')->default('AGGREGATOR');$t->timestamp('official_checked_at')->nullable();$t->boolean('official_open')->nullable();$t->string('official_url',2048)->nullable();});
        Schema::table('galika_applications', function(Blueprint $t){$t->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();$t->timestamp('next_retry_at')->nullable()->index();$t->unsignedInteger('max_attempts')->default(5);$t->string('failure_class')->nullable()->index();$t->string('follow_up_state')->nullable();$t->timestamp('follow_up_due')->nullable()->index();});
    }
    public function down(): void {
        Schema::table('galika_applications', function(Blueprint $t){$t->dropConstrainedForeignId('user_id');$t->dropColumn(['next_retry_at','max_attempts','failure_class','follow_up_state','follow_up_due']);});
        Schema::table('galika_opportunities', function(Blueprint $t){$t->dropColumn(['fingerprint','requisition_id','source_authority','official_checked_at','official_open','official_url']);});
        foreach(['galika_audit_events','galika_delivery_events','galika_email_routes','galika_policies','galika_integrations','galika_assets','galika_evidence','galika_preferences','galika_profiles'] as $x) Schema::dropIfExists($x);
    }
};
