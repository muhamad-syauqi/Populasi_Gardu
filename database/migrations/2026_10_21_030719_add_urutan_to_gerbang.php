<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gerbang', function (Blueprint $table) {
            $table->unsignedSmallInteger('urutan')->nullable()->after('lokasi');
        });
    }

    public function down(): void
    {
        Schema::table('gerbang', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });
    }
};