<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up():void{
  Schema::create('contracts',function(Blueprint $t){$t->id();$t->foreignId('job_id')->constrained()->cascadeOnDelete();$t->foreignId('employer_id')->constrained('users')->cascadeOnDelete();$t->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();$t->string('state')->default('DRAFT');$t->decimal('value',16,2)->default(0);$t->string('currency',3)->default('USD');$t->timestamps();});
  Schema::create('milestones',function(Blueprint $t){$t->id();$t->foreignId('contract_id')->constrained()->cascadeOnDelete();$t->string('title');$t->decimal('amount',16,2);$t->string('state')->default('PENDING');$t->timestamp('due_at')->nullable();$t->timestamps();});
  Schema::create('payments',function(Blueprint $t){$t->id();$t->foreignId('contract_id')->constrained()->cascadeOnDelete();$t->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('payer_id')->constrained('users')->cascadeOnDelete();$t->foreignId('payee_id')->constrained('users')->cascadeOnDelete();$t->decimal('amount',16,2);$t->string('currency',3)->default('USD');$t->string('state')->default('PENDING');$t->string('provider')->nullable();$t->string('provider_reference')->nullable()->index();$t->json('proof')->nullable();$t->timestamps();});
 }
 public function down():void{Schema::dropIfExists('payments');Schema::dropIfExists('milestones');Schema::dropIfExists('contracts');}
};