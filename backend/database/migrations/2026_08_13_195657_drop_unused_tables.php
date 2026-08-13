<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops tables confirmed dead by static analysis (no Model, no controller/route/seeder
 * reference outside their own migration): incident_routes, reports, community_feedback -
 * plus Laravel's default cache/queue scaffolding tables, which this app never activates
 * (CACHE_STORE=file, QUEUE_CONNECTION=sync, zero ShouldQueue classes anywhere in app/).
 * down() recreates all 8 from their original Schema::create definitions so this is a real,
 * tested rollback rather than a one-way trip against production data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('incident_routes');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('community_feedback');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }

    public function down(): void
    {
        Schema::create('incident_routes', function (Blueprint $table) {
            $table->id('route_id');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id')->cascadeOnDelete();
            $table->foreignId('ranger_id')->constrained('users', 'user_id');
            $table->longText('route_data');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->foreignId('generated_by')->constrained('users', 'user_id');
            $table->string('report_type', 100);
            $table->timestamp('generated_at')->useCurrent();
        });

        Schema::create('community_feedback', function (Blueprint $table) {
            $table->id('feedback_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->enum('feedback_type', ['Complaint', 'Suggestion', 'Appreciation']);
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        });
    }
};
