<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('status', 20)->default('active')->after('password')->index();
            $table->timestampTz('last_login_at')->nullable();
            $table->string('locale', 5)->default('en');
            // Filament app-based two-factor authentication
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->softDeletes();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active','suspended'))");
        DB::statement('CREATE UNIQUE INDEX users_phone_unique ON users (phone) WHERE phone IS NOT NULL AND deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email)) WHERE deleted_at IS NULL');
        Schema::table('users', fn (Blueprint $t) => $t->dropUnique('users_email_unique'));
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_lower_unique');
        DB::statement('DROP INDEX IF EXISTS users_phone_unique');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_status_check');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
            $table->dropColumn(['phone', 'status', 'last_login_at', 'locale', 'app_authentication_secret', 'app_authentication_recovery_codes', 'deleted_at']);
        });
    }
};
