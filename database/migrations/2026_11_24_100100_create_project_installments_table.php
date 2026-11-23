<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Public instalment schedule for a project (a plan template; per-buyer collection comes with the ERP phase, docs/27). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('label', 120);
            $table->string('timing', 120)->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_installments');
    }
};
