<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perangkat_gardu', function (Blueprint $table) {
            $table->unsignedInteger('lpr_custom_atas')->default(0)->after('cctv_gandar');
            $table->unsignedInteger('lpr_custom_bawah')->default(0)->after('lpr_custom_atas');
        });

        // Nilai lama (satu kolom) dipindahkan ke "Atas"
        DB::table('perangkat_gardu')->update(['lpr_custom_atas' => DB::raw('COALESCE(lpr_custom, 0)')]);

        Schema::table('perangkat_gardu', function (Blueprint $table) {
            $table->dropColumn('lpr_custom');
        });
    }

    public function down(): void
    {
        Schema::table('perangkat_gardu', function (Blueprint $table) {
            $table->unsignedInteger('lpr_custom')->default(0)->after('cctv_gandar');
        });

        DB::table('perangkat_gardu')->update(['lpr_custom' => DB::raw('lpr_custom_atas + lpr_custom_bawah')]);

        Schema::table('perangkat_gardu', function (Blueprint $table) {
            $table->dropColumn(['lpr_custom_atas', 'lpr_custom_bawah']);
        });
    }
};