<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Minimal, forward-compatible lead store (ADR-0007/0016). Phase 8 extends it.
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->string('request_type', 30);
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('developer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('new');
            $table->jsonb('meta')->nullable();
            $table->timestampTz('consent_given_at')->nullable();
            $table->string('consent_text_version', 20)->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('landing_url', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('status');
            $table->index('phone');
            $table->index('created_at');
            $table->index('request_type');
        });

        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('preferred_date');
            $table->string('preferred_window', 20); // morning|afternoon|evening
            $table->string('status', 20)->default('requested');
            $table->timestampTz('scheduled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'preferred_date']);
        });

        DB::statement("ALTER TABLE leads ADD CONSTRAINT leads_status_check CHECK (status IN ('new','contacted','qualified','interested','site_visit','negotiation','converted','lost','not_interested','invalid','duplicate'))");
        DB::statement("ALTER TABLE site_visits ADD CONSTRAINT site_visits_status_check CHECK (status IN ('requested','confirmed','rescheduled','completed','cancelled','no_show'))");
        DB::statement('ALTER TABLE site_visits ADD CONSTRAINT site_visits_target_check CHECK (property_id IS NOT NULL OR project_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
        Schema::dropIfExists('leads');
    }
};
