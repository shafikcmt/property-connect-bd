<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('public_code', 20)->nullable()->unique();
            $table->string('slug')->nullable()->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('purpose', 10); // sale|rent
            $table->foreignId('property_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('developer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_unit_id')->nullable()->constrained('project_units')->nullOnDelete();
            $table->string('address_line')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->boolean('price_negotiable')->default(false);
            $table->string('rent_period', 10)->nullable(); // monthly|yearly
            $table->decimal('size_sqft', 10, 2)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('balconies')->nullable();
            $table->smallInteger('floor_no')->nullable();
            $table->unsignedSmallInteger('total_floors')->nullable();
            $table->string('facing', 20)->nullable();
            $table->unsignedSmallInteger('parking_spaces')->nullable();
            $table->string('furnishing', 20)->nullable();
            $table->string('property_status', 20)->default('ready'); // ready|under_construction|upcoming
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->date('handover_on')->nullable();
            $table->text('payment_plan')->nullable();
            $table->jsonb('nearby')->nullable();
            $table->string('video_url')->nullable();
            $table->string('publish_status', 20)->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('price_updated_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestampTz('featured_until')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestampTz('verified_at')->nullable();
            $table->boolean('show_owner_contact')->default(false);
            $table->string('owner_name')->nullable();      // private: never rendered publicly
            $table->string('owner_phone', 30)->nullable(); // private
            $table->string('owner_email')->nullable();     // private
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('view_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['publish_status', 'purpose', 'property_type_id', 'location_id'], 'properties_listing_idx');
            $table->index('price');
            $table->index('size_sqft');
            $table->index('bedrooms');
            $table->index(['is_featured', 'featured_until']);
            $table->index('published_at');
            $table->index('developer_id');
            $table->index('project_id');
        });

        DB::statement("ALTER TABLE properties ADD CONSTRAINT properties_publish_status_check CHECK (publish_status IN ('draft','pending_review','published','rejected','archived','sold','rented'))");
        DB::statement("ALTER TABLE properties ADD CONSTRAINT properties_purpose_check CHECK (purpose IN ('sale','rent'))");
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_price_check CHECK (price IS NULL OR price >= 0)');
        DB::statement('ALTER TABLE properties ADD CONSTRAINT properties_size_check CHECK (size_sqft IS NULL OR size_sqft > 0)');

        Schema::create('amenity_property', function (Blueprint $table) {
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->primary(['amenity_id', 'property_id']);
        });

        Schema::create('amenity_project', function (Blueprint $table) {
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['amenity_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_project');
        Schema::dropIfExists('amenity_property');
        Schema::dropIfExists('properties');
    }
};
