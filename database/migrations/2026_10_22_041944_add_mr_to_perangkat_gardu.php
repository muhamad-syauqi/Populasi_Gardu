<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perangkat_gardu', function (Blueprint $table) {
            $table->unsignedInteger('mr_entrance')->default(0)->after('cctv_gandar');
            $table->unsignedInteger('mr_exit')->default(0)->after('mr_entrance');
        });
    }

    public function down(): void
    {
        Schema::table('perangkat_gardu', function (Blueprint $table) {
            $table->dropColumn(['mr_entrance', 'mr_exit']);
        });
    }
};