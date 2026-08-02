<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('district', 100)->nullable()->after('village');
            $table->string('sub_county', 100)->nullable()->after('district');
            $table->string('parish', 100)->nullable()->after('sub_county');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['district', 'sub_county', 'parish']);
        });
    }
};
