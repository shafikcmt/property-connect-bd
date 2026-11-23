<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ADR-0017: own media layer. Floor plans are the `floor_plans` collection.
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable');
            $table->string('collection', 30)->default('gallery'); // gallery|cover|logo|floor_plans|brochure|documents
            $table->string('disk', 20)->default('public');
            $table->string('path');           // original (or directory for image derivatives)
            $table->string('filename');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('alt')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_private')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->timestampsTz();

            $table->index(['mediable_type', 'mediable_id', 'collection', 'sort_order'], 'media_lookup_idx');
        });

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifiable');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('verified_on');
            $table->string('method', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 10)->default('verified'); // verified|revoked
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestampsTz();
        });

        Schema::create('slug_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('sluggable');
            $table->string('slug');
            $table->timestampTz('created_at')->useCurrent();
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_histories');
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('media');
    }
};
