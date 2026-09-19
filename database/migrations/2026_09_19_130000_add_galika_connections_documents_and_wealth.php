<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('galika_connections', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider'); $t->string('auth_type'); $t->string('account_label')->nullable();
            $t->text('access_token')->nullable(); $t->text('refresh_token')->nullable(); $t->text('api_key')->nullable();
            $t->timestamp('expires_at')->nullable(); $t->json('scopes')->nullable(); $t->json('metadata')->nullable();
            $t->string('health')->default('UNKNOWN'); $t->text('last_error')->nullable(); $t->timestamp('last_health_at')->nullable();
            $t->timestamps(); $t->unique(['user_id','provider']);
        });
        Schema::create('galika_documents', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('kind'); $t->string('disk')->default('local');
            $t->string('path'); $t->string('original_name'); $t->string('mime'); $t->unsignedBigInteger('size'); $t->string('sha256',64);
            $t->longText('extracted_text')->nullable(); $t->json('extracted_facts')->nullable(); $t->boolean('confirmed')->default(false); $t->timestamps();
        });
        Schema::create('galika_recruiter_threads', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->foreignId('application_id')->nullable()->constrained('galika_applications')->nullOnDelete();
            $t->string('provider_thread_id')->nullable(); $t->string('classification')->default('OTHER'); $t->string('state')->default('OPEN');
            $t->json('participants')->nullable(); $t->text('last_message')->nullable(); $t->timestamp('last_message_at')->nullable(); $t->boolean('requires_human')->default(false); $t->timestamps();
        });
        Schema::create('galika_wealth_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('lane'); $t->string('title'); $t->string('counterparty')->nullable();
            $t->text('source_url')->nullable(); $t->string('state')->default('DISCOVERED'); $t->unsignedInteger('priority')->default(100);
            $t->decimal('estimated_value',16,2)->nullable(); $t->string('currency',3)->nullable(); $t->timestamp('next_action_at')->nullable(); $t->json('evidence')->nullable();
            $t->json('execution_plan')->nullable(); $t->text('last_error')->nullable(); $t->timestamps();
        });
        Schema::create('galika_canary_runs', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $t->uuid('run_key')->unique(); $t->string('scenario'); $t->string('state')->default('PENDING');
            $t->json('steps')->nullable(); $t->json('evidence')->nullable(); $t->timestamp('started_at')->nullable(); $t->timestamp('finished_at')->nullable(); $t->text('failure')->nullable(); $t->timestamps();
        });
        Schema::table('galika_applications', function (Blueprint $t) {
            $t->string('ats_type')->nullable()->index(); $t->string('ats_requisition_id')->nullable()->index();
            $t->string('resume_document_id')->nullable(); $t->string('cover_letter_document_id')->nullable();
            $t->string('recruiter_thread_state')->nullable(); $t->timestamp('last_inbound_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('galika_applications', fn(Blueprint $t)=>$t->dropColumn(['ats_type','ats_requisition_id','resume_document_id','cover_letter_document_id','recruiter_thread_state','last_inbound_at']));
        foreach(['galika_canary_runs','galika_wealth_items','galika_recruiter_threads','galika_documents','galika_connections'] as $x) Schema::dropIfExists($x);
    }
};
