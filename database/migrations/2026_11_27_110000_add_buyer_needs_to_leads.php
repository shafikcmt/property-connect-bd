<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** What the buyer is looking for (docs/13 §2): needed before a lead can be called qualified. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->decimal('budget_min', 14, 2)->nullable();
            $table->decimal('budget_max', 14, 2)->nullable();
            $table->foreignId('preferred_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('property_type_id')->nullable()->constrained('property_types')->nullOnDelete();
            $table->string('purpose', 10)->nullable();
            $table->string('buy_timeline', 20)->nullable();
        });

        DB::statement("alter table leads add constraint leads_needs_check check ((purpose is null or purpose in ('sale','rent')) and (buy_timeline is null or buy_timeline in ('immediately','within_3_months','within_6_months','later','just_exploring')) and (budget_min is null or budget_max is null or budget_min <= budget_max))");
    }

    public function down(): void
    {
        DB::statement('alter table leads drop constraint if exists leads_needs_check');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_location_id');
            $table->dropConstrainedForeignId('property_type_id');
            $table->dropColumn(['budget_min', 'budget_max', 'purpose', 'buy_timeline']);
        });
    }
};
