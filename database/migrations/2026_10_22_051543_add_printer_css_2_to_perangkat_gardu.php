<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perangkat_gardu', function (Blueprint $table) {
            $table->string('printer_css_2')->nullable()->after('printer_css');
        });
    }

    public function down(): void
    {
        Schema::table('perangkat_gardu', function (Blueprint $table) {
            $table->dropColumn('printer_css_2');
        });
    }
};