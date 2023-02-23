<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->unsigned();
            $table->string('headline');
            $table->string('title');
            $table->string('job_id')->unique();
            $table->bigInteger('category_id')->nullable()->unsigned();
            $table->string('description');
            $table->string('skills');
            $table->string('payment_category')->nullabe();
            $table->string('pay_rate')->nullabe();
            $table->string('status')->nullable();
            $table->foreign('category_id')->references('id')->on('job_categories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('task_deadline')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
};
