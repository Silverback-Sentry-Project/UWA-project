<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Media registry (borrowed from the ww-website media rendition pipeline, ranked
 * borrowing list item 3).
 *
 * Records every image referenced by a portal resource plus the derived Cloudinary
 * transform URLs for the thumbnail / preview renditions. Keeping original ->
 * derivative mappings in the database means the portal and the Android app can
 * ask "what size should I request for this context?" without re-deriving the
 * transform each time, and lets administration soft-delete a media row so an
 * orphaned original no longer keeps derivatives alive.
 *
 * Columns intentionally mirror the incidents soft-delete convention (deleted_at +
 * deleted_by) so removals are auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_registry', function (Blueprint $table) {
            $table->id('registry_id');
            $table->nullableMorphs('owner');
            $table->string('kind', 20)->default('image');
            $table->string('original_url', 2048);
            $table->string('thumbnail_url', 2048)->nullable();
            $table->string('preview_url', 2048)->nullable();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_registry');
    }
};