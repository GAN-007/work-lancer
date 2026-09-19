<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('galika_work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->nullable()->constrained('galika_opportunities')->nullOnDelete();
            $table->string('action');
            $table->string('status')->default('READY');
            $table->unsignedInteger('priority')->default(100);
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('lease_owner')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at']);
            $table->index(['action', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('galika_work_items');
    }
};
