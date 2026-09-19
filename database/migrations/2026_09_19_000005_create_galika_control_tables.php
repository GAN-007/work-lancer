<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('galika_candidate_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value');
            $table->string('value_type')->default('text');
            $table->text('source_basis')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });

        Schema::create('galika_source_registry', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('source_class');
            $table->string('health_status')->default('UNKNOWN');
            $table->string('circuit_state')->default('CLOSED');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();
            $table->decimal('semantic_quality_score', 5, 2)->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('galika_platform_health', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->unique();
            $table->string('health_status')->default('UNKNOWN');
            $table->string('circuit_state')->default('CLOSED');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('detail')->nullable();
            $table->timestamps();
        });

        Schema::create('galika_decision_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->nullable()->constrained('galika_opportunities')->nullOnDelete();
            $table->string('decision_type');
            $table->text('question');
            $table->text('why_unknown')->nullable();
            $table->text('source_url')->nullable();
            $table->string('status')->default('OPEN');
            $table->longText('resolved_value')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('galika_email_route_registry', function (Blueprint $table) {
            $table->id();
            $table->string('address')->unique();
            $table->string('domain')->nullable();
            $table->string('evidence_type');
            $table->text('evidence_url')->nullable();
            $table->string('route_state')->default('UNVERIFIED');
            $table->boolean('do_not_retry')->default(false);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_bounce_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('galika_learning_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->nullable()->constrained('galika_opportunities')->nullOnDelete();
            $table->string('event_type');
            $table->string('source_name')->nullable();
            $table->string('role_family')->nullable();
            $table->string('route')->nullable();
            $table->string('positioning_key')->nullable();
            $table->decimal('application_age_hours', 8, 2)->nullable();
            $table->text('evidence')->nullable();
            $table->decimal('value_score', 10, 2)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('galika_revenue_pipeline', function (Blueprint $table) {
            $table->id();
            $table->string('engine');
            $table->string('opportunity_type');
            $table->string('counterparty')->nullable();
            $table->string('status')->default('DISCOVERED');
            $table->decimal('expected_value', 14, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('next_action')->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->text('evidence')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('galika_revenue_pipeline');
        Schema::dropIfExists('galika_learning_log');
        Schema::dropIfExists('galika_email_route_registry');
        Schema::dropIfExists('galika_decision_queue');
        Schema::dropIfExists('galika_platform_health');
        Schema::dropIfExists('galika_source_registry');
        Schema::dropIfExists('galika_candidate_knowledge');
    }
};
