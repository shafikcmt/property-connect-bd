<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('developers', function (Blueprint $table) {
            $table->timestampTz('published_at')->nullable()->after('publish_status');
        });
    }

    public function down(): void
    {
        Schema::table('developers', fn (Blueprint $table) => $table->dropColumn('published_at'));
    }
};
