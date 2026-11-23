<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->jsonb('filters');
            $table->timestampsTz();
        });

        Schema::create('property_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->string('contact', 255)->nullable();
            $table->string('status', 20)->default('open');
            $table->string('ip_hash', 64)->nullable();
            $table->timestampsTz();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestampTz('consent_at');
            $table->timestampTz('unsubscribed_at')->nullable();
            $table->string('token', 64)->unique();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('property_reports');
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('favorites');
    }
};
