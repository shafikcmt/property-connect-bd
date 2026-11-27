<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Site-visit workflow (docs/13 §5): who takes the visit, where to meet, outcome and why it was cancelled. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('meeting_point', 255)->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->text('feedback')->nullable();
            $table->string('cancel_reason', 500)->nullable();

            $table->index('assigned_to');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['meeting_point', 'confirmed_at', 'completed_at', 'feedback', 'cancel_reason']);
        });
    }
};
