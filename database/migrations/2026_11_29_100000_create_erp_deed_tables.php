<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Developer ERP, part 3: registered deeds (one deed lists many shares) and mutation (নামজারি) per share. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deed_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('deed_number', 60)->nullable();
            $table->string('registry_office', 150)->nullable();
            $table->date('registered_on')->nullable();
            $table->string('status', 12)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['developer_id', 'project_id']);
        });
        DB::statement("alter table deed_batches add constraint deed_batches_status_check check (status in ('draft','registered'))");
        // A registered deed must carry its number, office and date.
        DB::statement("alter table deed_batches add constraint deed_batches_registered_check check (status = 'draft' or (deed_number is not null and registry_office is not null and registered_on is not null))");

        Schema::create('deed_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('deed_batch_id')->constrained('deed_batches')->cascadeOnDelete();
            // One sale appears in at most one deed.
            $table->foreignId('share_holding_id')->unique()->constrained('share_holdings')->restrictOnDelete();
            $table->string('mutation_status', 10)->default('none');
            $table->date('mutation_applied_on')->nullable();
            $table->date('mutation_completed_on')->nullable();
            $table->string('mutation_reference', 100)->nullable();
            $table->timestampsTz();

            $table->index('deed_batch_id');
        });
        DB::statement("alter table deed_entries add constraint deed_entries_mutation_check check (mutation_status in ('none','applied','completed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('deed_entries');
        Schema::dropIfExists('deed_batches');
    }
};
