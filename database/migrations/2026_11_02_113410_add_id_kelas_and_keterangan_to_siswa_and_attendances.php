<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom id_kelas ke tabel siswa agar siswa terhubung dengan kelas
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('id_kelas', 20)->nullable()->after('nama_siswa');
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->onDelete('set null');
        });

        // Tambah kolom keterangan ke tabel attendances untuk catatan absensi
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('keterangan', 255)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropForeign(['id_kelas']);
            $table->dropColumn('id_kelas');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
