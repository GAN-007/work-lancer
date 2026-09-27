<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('galika_customers')) {
            Schema::create('galika_customers', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->string('name');
                $t->string('email')->nullable()->index();
                $t->string('phone')->nullable()->index();
                $t->string('company')->nullable()->index();
                $t->string('state')->default('PROSPECT')->index();
                $t->string('currency',3)->default('KES');
                $t->decimal('lifetime_value',16,2)->default(0);
                $t->timestampTz('won_at')->nullable();
                $t->timestampTz('churned_at')->nullable();
                $t->json('metadata')->nullable();
                $t->timestampsTz();
                $t->index(['user_id','state']);
            });
        }

        if (!Schema::hasTable('galika_invoices')) {
            Schema::create('galika_invoices', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->foreignId('customer_id')->nullable()->constrained('galika_customers')->nullOnDelete();
                $t->foreignId('wealth_item_id')->nullable()->constrained('galika_wealth_items')->nullOnDelete();
                $t->string('invoice_number')->unique();
                $t->string('currency',3)->default('KES');
                $t->decimal('subtotal',16,2);
                $t->decimal('tax_amount',16,2)->default(0);
                $t->decimal('total',16,2);
                $t->decimal('amount_paid',16,2)->default(0);
                $t->string('state')->default('DRAFT')->index();
                $t->timestampTz('issued_at')->nullable();
                $t->timestampTz('due_at')->nullable();
                $t->timestampTz('paid_at')->nullable();
                $t->json('lines');
                $t->json('metadata')->nullable();
                $t->timestampsTz();
                $t->index(['user_id','state','issued_at']);
            });
        }

        if (!Schema::hasTable('galika_payment_transactions')) {
            Schema::create('galika_payment_transactions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->foreignId('invoice_id')->nullable()->constrained('galika_invoices')->nullOnDelete();
                $t->foreignId('customer_id')->nullable()->constrained('galika_customers')->nullOnDelete();
                $t->string('provider')->index();
                $t->string('provider_reference')->nullable()->index();
                $t->string('idempotency_key')->unique();
                $t->string('currency',3)->default('KES');
                $t->decimal('amount',16,2);
                $t->string('state')->default('PENDING')->index();
                $t->timestampTz('initiated_at')->nullable();
                $t->timestampTz('confirmed_at')->nullable();
                $t->timestampTz('failed_at')->nullable();
                $t->json('request_payload')->nullable();
                $t->json('provider_payload')->nullable();
                $t->text('failure_reason')->nullable();
                $t->timestampsTz();
                $t->index(['user_id','state','confirmed_at']);
            });
        }

        if (!Schema::hasTable('galika_subscriptions')) {
            Schema::create('galika_subscriptions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->foreignId('customer_id')->constrained('galika_customers')->cascadeOnDelete();
                $t->foreignId('wealth_item_id')->nullable()->constrained('galika_wealth_items')->nullOnDelete();
                $t->string('name');
                $t->string('currency',3)->default('KES');
                $t->decimal('recurring_amount',16,2);
                $t->string('interval')->default('MONTHLY');
                $t->string('state')->default('ACTIVE')->index();
                $t->timestampTz('started_at');
                $t->timestampTz('current_period_start')->nullable();
                $t->timestampTz('current_period_end')->nullable();
                $t->timestampTz('cancelled_at')->nullable();
                $t->json('metadata')->nullable();
                $t->timestampsTz();
                $t->index(['user_id','state']);
            });
        }

        if (!Schema::hasTable('galika_outbox_dead_letters')) {
            Schema::create('galika_outbox_dead_letters', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('outbox_id')->index();
                $t->unsignedBigInteger('user_id')->nullable()->index();
                $t->string('destination');
                $t->string('kind');
                $t->json('payload');
                $t->unsignedInteger('attempts');
                $t->text('last_error');
                $t->string('state')->default('OPEN')->index();
                $t->timestampTz('dead_lettered_at')->index();
                $t->timestampTz('replayed_at')->nullable();
                $t->timestampsTz();
                $t->unique('outbox_id');
            });
        }

        if (!Schema::hasTable('galika_backup_checks')) {
            Schema::create('galika_backup_checks', function (Blueprint $t) {
                $t->id();
                $t->string('kind')->index();
                $t->string('state')->index();
                $t->string('database_name')->nullable();
                $t->unsignedBigInteger('table_count')->nullable();
                $t->string('artifact_path')->nullable();
                $t->string('checksum')->nullable();
                $t->json('details')->nullable();
                $t->timestampTz('checked_at')->index();
                $t->timestampsTz();
            });
        }
    }

    public function down(): void {
        foreach ([
            'galika_backup_checks',
            'galika_outbox_dead_letters',
            'galika_subscriptions',
            'galika_payment_transactions',
            'galika_invoices',
            'galika_customers'
        ] as $table) Schema::dropIfExists($table);
    }
};
