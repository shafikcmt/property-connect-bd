<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Land-share projects (docs/27): share counts, per-share price, and the optional branded landing page switch. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_share_project')->default(false)->after('payment_plan');
            $table->unsignedSmallInteger('total_shares')->nullable()->after('is_share_project');
            $table->unsignedSmallInteger('sold_shares')->nullable()->after('total_shares');
            $table->unsignedSmallInteger('registered_shares')->nullable()->after('sold_shares');
            $table->decimal('price_per_share', 14, 2)->nullable()->after('registered_shares');
            $table->decimal('land_area_shatangsho', 10, 3)->nullable()->after('price_per_share');
            $table->unsignedTinyInteger('buildings_count')->nullable()->after('land_area_shatangsho');
            $table->boolean('shares_verified')->default(false)->after('buildings_count');
            $table->boolean('branded_landing')->default(false)->after('shares_verified');
        });

        DB::statement('alter table projects add constraint projects_shares_order_check check ('
            .'(sold_shares is null or total_shares is null or sold_shares <= total_shares) and '
            .'(registered_shares is null or sold_shares is null or registered_shares <= sold_shares))');
    }

    public function down(): void
    {
        DB::statement('alter table projects drop constraint if exists projects_shares_order_check');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['is_share_project', 'total_shares', 'sold_shares', 'registered_shares', 'price_per_share', 'land_area_shatangsho', 'buildings_count', 'shares_verified', 'branded_landing']);
        });
    }
};
