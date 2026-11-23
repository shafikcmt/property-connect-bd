<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Dated milestones shown as a public timeline on the project page (e.g. "Second deed batch completed"). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('body')->nullable();
            $table->date('happened_on');
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'happened_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_updates');
    }
};
