<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Reference data only. Demo data is never seeded here (docs/10 §7). */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
