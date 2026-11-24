<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Phase 8 (docs/13): assignment, priority, follow-up and response tracking on the existing leads table. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestampTz('assigned_at')->nullable();
            $table->string('priority', 10)->default('normal');
            $table->timestampTz('next_follow_up_at')->nullable();
            $table->timestampTz('last_activity_at')->nullable();
            $table->timestampTz('first_contacted_at')->nullable();
            $table->string('lost_reason', 500)->nullable();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('leads')->nullOnDelete();

            $table->index('assigned_to');
            $table->index('next_follow_up_at');
        });

        DB::statement("alter table leads add constraint leads_priority_check check (priority in ('low','normal','high','urgent'))");
    }

    public function down(): void
    {
        DB::statement('alter table leads drop constraint if exists leads_priority_check');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('duplicate_of_id');
            $table->dropColumn(['assigned_at', 'priority', 'next_follow_up_at', 'last_activity_at', 'first_contacted_at', 'lost_reason']);
        });
    }
};
