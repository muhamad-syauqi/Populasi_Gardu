
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruas_tol', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ruas')->unique();
            $table->timestamps();
        });

        Schema::create('gerbang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruas_id')
                ->constrained('ruas_tol')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nama_gerbang');
            $table->timestamps();

            $table->unique(['ruas_id', 'nama_gerbang']);
        });

        Schema::create('tipe_gardu', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tipe')->unique();
            $table->timestamps();
        });

        Schema::create('gardu', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gerbang_id')
                ->constrained('gerbang')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nomor_gardu', 10);

            $table->foreignId('tipe_gardu_id')
                ->nullable()
                ->constrained('tipe_gardu')
                ->nullOnDelete();

            $table->enum('arah', ['Entrance', 'Exit'])->nullable();

            $table->enum('status', [
                'Aktif',
                'Tidak Aktif',
                'Pemeliharaan'
            ])->default('Aktif');

            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['gerbang_id', 'nomor_gardu']);
        });

        Schema::create('perangkat_gardu', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gardu_id')
                ->unique()
                ->constrained('gardu')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedInteger('reader_atas')->default(0);
            $table->unsignedInteger('reader_bawah')->default(0);
            $table->unsignedInteger('cctv_lajur')->default(0);
            $table->unsignedInteger('cctv_gandar')->default(0);
            $table->unsignedInteger('lpr_custom')->default(0);
            $table->unsignedInteger('pyramid_atas')->default(0);
            $table->unsignedInteger('pyramid_bawah')->default(0);
            $table->unsignedInteger('hs_atas')->default(0);
            $table->unsignedInteger('hs_bawah')->default(0);
            $table->unsignedInteger('alb_tollpro')->default(0);
            $table->unsignedInteger('alb_highspeed')->default(0);
            $table->unsignedInteger('alb_grs')->default(0);
            $table->unsignedInteger('alb_automation')->default(0);

            $table->string('printer_css')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat_gardu');
        Schema::dropIfExists('gardu');
        Schema::dropIfExists('tipe_gardu');
        Schema::dropIfExists('gerbang');
        Schema::dropIfExists('ruas_tol');
    }
};
