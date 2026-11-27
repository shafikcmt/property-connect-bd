<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Developer ERP, part 1: shares, shareholders and who holds which share (docs/27). Every row belongs to one developer. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('number');
            $table->decimal('area_shatangsho', 10, 4)->nullable();
            $table->decimal('list_price', 14, 2)->nullable();
            $table->string('status', 12)->default('available');
            $table->timestampsTz();

            $table->unique(['project_id', 'number']);
            $table->index(['developer_id', 'status']);
        });
        DB::statement("alter table project_shares add constraint project_shares_status_check check (status in ('available','reserved','sold'))");

        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('father_name', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('nid')->nullable();             // encrypted by the model cast
            $table->string('nid_last4', 4)->nullable();   // for masked display and lookup only
            $table->text('notes')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['developer_id', 'name']);
            $table->index(['developer_id', 'phone']);
        });

        Schema::create('share_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_share_id')->constrained('project_shares')->restrictOnDelete();
            $table->foreignId('shareholder_id')->constrained('shareholders')->restrictOnDelete();
            $table->decimal('price', 14, 2);
            $table->date('sold_on');
            $table->string('status', 10)->default('active');
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['developer_id', 'status']);
            $table->index('shareholder_id');
        });
        DB::statement("alter table share_holdings add constraint share_holdings_status_check check (status in ('active','cancelled'))");
        DB::statement('alter table share_holdings add constraint share_holdings_price_check check (price > 0)');
        // A share can be held by one person at a time; a cancelled sale frees it for the next buyer.
        DB::statement("create unique index share_holdings_one_active on share_holdings (project_share_id) where status = 'active'");
    }

    public function down(): void
    {
        Schema::dropIfExists('share_holdings');
        Schema::dropIfExists('shareholders');
        Schema::dropIfExists('project_shares');
    }
};
