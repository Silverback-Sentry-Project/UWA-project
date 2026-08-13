<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone_number', 20)->nullable()->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('password_hash', 255);
            $table->string('preferred_language', 50)->default('English');
            $table->enum('account_status', ['Pending', 'Active', 'Suspended'])->default('Pending');
            $table->boolean('email_verified')->default(false);
            $table->boolean('phone_verified')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        // Mirrors the CHECK (phone_number IS NOT NULL OR email IS NOT NULL) constraint from the
        // source SQL script. Supported by both MySQL 8.0.16+ and Postgres (the real production
        // driver via Neon - see HOSTED-CUTOVER-PLAN.md) using this exact syntax, but not by the
        // SQLite version PHP 8.4 ships with (the test suite's driver - phpunit.xml), which
        // rejects ALTER TABLE ... ADD CONSTRAINT outright. This previously went unnoticed
        // locally only because this machine happens to have an unusually new system sqlite3
        // that tolerates it - confirmed by testing the same statement directly against both.
        // Skipped rather than adapted for SQLite, since it has no post-creation CHECK-constraint
        // equivalent and no test relies on this rule being DB-enforced.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE users ADD CONSTRAINT chk_contact_method CHECK (phone_number IS NOT NULL OR email IS NOT NULL)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
