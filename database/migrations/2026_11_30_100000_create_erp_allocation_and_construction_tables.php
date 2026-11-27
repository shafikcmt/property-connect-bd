<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Developer ERP, part 4: which flat each shareholder gets, the handover checklist, and construction milestones. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flat_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('share_holding_id')->unique()->constrained('share_holdings')->restrictOnDelete();
            $table->string('building', 40)->default('');
            $table->string('floor', 20);
            $table->string('flat_no', 20);
            $table->string('method', 12);
            $table->date('allocated_on');
            $table->jsonb('checklist')->nullable();
            $table->string('status', 12)->default('allocated');
            $table->date('handed_over_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            // One flat goes to one shareholder.
            $table->unique(['project_id', 'building', 'floor', 'flat_no']);
        });
        DB::statement("alter table flat_allocations add constraint flat_allocations_status_check check (status in ('allocated','handed_over'))");
        DB::statement("alter table flat_allocations add constraint flat_allocations_method_check check (method in ('lottery','choice','assigned'))");

        Schema::create('construction_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('title', 160);
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('progress');
            $table->date('happened_on');
            $table->foreignId('project_update_id')->nullable()->constrained('project_updates')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['project_id', 'happened_on']);
        });
        DB::statement('alter table construction_milestones add constraint construction_milestones_progress_check check (progress between 0 and 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('construction_milestones');
        Schema::dropIfExists('flat_allocations');
    }
};
