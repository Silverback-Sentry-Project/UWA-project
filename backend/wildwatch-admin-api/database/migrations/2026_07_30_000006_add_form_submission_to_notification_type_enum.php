<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL/MariaDB: widen the enum. (No-op-safe for re-runs since it just redefines the column.)
        DB::statement("ALTER TABLE notifications MODIFY notification_type ENUM('SOS','Incident','Assignment','Compensation','General','FormSubmission') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY notification_type ENUM('SOS','Incident','Assignment','Compensation','General') NOT NULL");
    }
};
