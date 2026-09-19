<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('galika_delivery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->nullable()->constrained('galika_opportunities')->nullOnDelete();
            $table->string('event_type');
            $table->string('route')->nullable();
            $table->string('recipient')->nullable();
            $table->string('external_message_id')->nullable();
            $table->text('evidence')->nullable();
            $table->timestamp('occurred_at');
            $table->boolean('terminal')->default(false);
            $table->foreignId('supersedes_event_id')->nullable()->constrained('galika_delivery_events')->nullOnDelete();
            $table->timestamps();

            $table->index(['opportunity_id', 'occurred_at']);
            $table->index(['event_type', 'terminal']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('galika_delivery_events');
    }
};
