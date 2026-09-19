<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('galika_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_key')->unique();
            $table->string('employer');
            $table->string('role_title');
            $table->string('requisition_id')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->text('authoritative_url')->nullable();
            $table->string('location_text')->nullable();
            $table->string('remote_policy')->nullable();
            $table->timestamp('publication_timestamp')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('qualification_state')->default('PENDING');
            $table->string('verification_state')->default('PENDING');
            $table->string('application_status')->default('DISCOVERED');
            $table->string('application_route')->nullable();
            $table->text('route_url')->nullable();
            $table->decimal('freshness_hours', 8, 2)->nullable();
            $table->decimal('fit_score', 5, 2)->nullable();
            $table->string('blocker_code')->nullable();
            $table->text('blocker_detail')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('interview_at')->nullable();
            $table->timestamp('offer_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_action_at')->nullable();
            $table->timestamps();

            $table->index(['application_status', 'next_action_at']);
            $table->index(['publication_timestamp', 'discovered_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('galika_opportunities');
    }
};
