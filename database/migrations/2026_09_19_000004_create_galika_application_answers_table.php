<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('galika_application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('galika_opportunities')->cascadeOnDelete();
            $table->text('question');
            $table->longText('answer');
            $table->string('answer_type')->nullable();
            $table->text('source_basis')->nullable();
            $table->boolean('humanized')->default(false);
            $table->boolean('submitted')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->string('platform')->nullable();
            $table->text('evidence_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('galika_application_answers');
    }
};
