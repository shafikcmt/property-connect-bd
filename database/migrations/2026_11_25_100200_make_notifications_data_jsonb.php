<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Filament's bell queries the notification payload with PostgreSQL JSON operators, which need a json(b) column. */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('alter table notifications alter column data type jsonb using data::jsonb');
    }

    public function down(): void
    {
        DB::statement('alter table notifications alter column data type text using data::text');
    }
};
