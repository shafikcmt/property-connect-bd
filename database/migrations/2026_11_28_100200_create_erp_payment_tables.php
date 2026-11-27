<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Developer ERP, part 2: instalment plan, per-holding schedule and append-only payments (ADR-0019). */
return new class extends Migration
{
    public function up(): void
    {
        // Template per project: "Booking ×1", "Construction ×24 monthly" ... copied onto each sale.
        Schema::create('instalment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('count')->default(1);
            $table->unsignedSmallInteger('start_after_months')->default(0);
            $table->unsignedSmallInteger('interval_months')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['project_id', 'sort_order']);
        });
        DB::statement('alter table instalment_plan_items add constraint instalment_plan_items_check check (amount > 0 and count between 1 and 360 and interval_months between 1 and 60)');

        Schema::create('share_instalments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('share_holding_id')->constrained('share_holdings')->cascadeOnDelete();
            $table->string('label', 140);
            $table->date('due_on');
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['share_holding_id', 'due_on']);
        });
        DB::statement('alter table share_instalments add constraint share_instalments_amount_check check (amount > 0)');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('share_holding_id')->constrained('share_holdings')->restrictOnDelete();
            $table->string('kind', 10)->default('payment');
            $table->decimal('amount', 14, 2);
            $table->date('received_on');
            $table->string('method', 14);
            $table->string('reference', 120)->nullable();
            $table->string('receipt_no', 30);
            $table->string('note', 500)->nullable();
            $table->foreignId('reverses_payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['developer_id', 'receipt_no']);
            $table->index(['share_holding_id', 'received_on']);
        });
        DB::statement("alter table payments add constraint payments_check check (amount > 0 and kind in ('payment','reversal') and method in ('cash','bank_transfer','bkash','nagad','cheque','other') and ((kind = 'reversal') = (reverses_payment_id is not null)))");
        DB::statement('create unique index payments_one_reversal on payments (reverses_payment_id) where reverses_payment_id is not null');

        // Money records are never edited or deleted; a mistake is fixed with a reversal row.
        DB::statement("create or replace function payments_are_append_only() returns trigger language plpgsql as \$\$ begin raise exception 'payments are append-only: record a reversal instead'; end \$\$");
        DB::statement('create trigger payments_no_update_delete before update or delete on payments for each row execute function payments_are_append_only()');
    }

    public function down(): void
    {
        DB::statement('drop trigger if exists payments_no_update_delete on payments');
        DB::statement('drop function if exists payments_are_append_only()');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('share_instalments');
        Schema::dropIfExists('instalment_plan_items');
    }
};
