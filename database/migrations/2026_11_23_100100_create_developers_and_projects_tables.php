<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->text('about')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->boolean('show_contact_publicly')->default(false);
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->string('publish_status', 20)->default('draft');
            $table->boolean('is_verified')->default(false);
            $table->timestampTz('verified_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestampTz('featured_until')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['publish_status', 'is_featured']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('summary', 400)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('upcoming'); // upcoming|ongoing|ready|completed
            $table->unsignedTinyInteger('construction_progress')->nullable();
            $table->unsignedSmallInteger('total_floors')->nullable();
            $table->unsignedSmallInteger('total_units')->nullable();
            $table->date('handover_on')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('price_from', 14, 2)->nullable();
            $table->decimal('price_to', 14, 2)->nullable();
            $table->boolean('price_verified')->default(false);
            $table->text('payment_plan')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('available_units_verified')->default(false);
            $table->string('publish_status', 20)->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestampTz('verified_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestampTz('featured_until')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['publish_status', 'status']);
            $table->index('developer_id');
            $table->index('location_id');
            $table->index(['is_featured', 'featured_until']);
        });

        Schema::create('project_unit_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->decimal('size_sqft_min', 10, 2)->nullable();
            $table->decimal('size_sqft_max', 10, 2)->nullable();
            $table->decimal('price_from', 14, 2)->nullable();
            $table->decimal('price_to', 14, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('project_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_type_id')->nullable()->constrained('project_unit_types')->nullOnDelete();
            $table->string('unit_no', 30);
            $table->smallInteger('floor')->nullable();
            $table->string('facing', 20)->nullable();
            $table->decimal('size_sqft', 10, 2)->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->string('status', 20)->default('available'); // available|booked|sold|hold
            $table->timestampsTz();

            $table->unique(['project_id', 'unit_no']);
            $table->index(['project_id', 'status']);
        });

        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK (status IN ('upcoming','ongoing','ready','completed'))");
        DB::statement("ALTER TABLE project_units ADD CONSTRAINT project_units_status_check CHECK (status IN ('available','booked','sold','hold'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('project_units');
        Schema::dropIfExists('project_unit_types');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('developers');
    }
};
