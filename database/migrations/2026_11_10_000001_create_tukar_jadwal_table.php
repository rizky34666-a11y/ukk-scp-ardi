<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tukar_jadwal')) {
            return;
        }

        Schema::create('tukar_jadwal', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['Ruangan', 'Jam']);
            $table->date('tanggal');
            $table->unsignedInteger('id_jadwal_pemohon');
            $table->unsignedInteger('id_jadwal_tujuan')->nullable();
            $table->unsignedInteger('id_guru_pemohon');
            $table->unsignedInteger('id_guru_tujuan')->nullable();
            $table->string('id_ruangan_diminta', 20)->nullable();
            $table->string('alasan', 255)->nullable();
            $table->json('perubahan')->nullable();
            $table->enum('status', ['Menunggu', 'Disetujui', 'Ditolak', 'Dibatalkan', 'Kedaluwarsa'])->default('Menunggu');
            $table->dateTime('batas_waktu');
            $table->string('catatan', 255)->nullable();
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'status']);
            $table->index('id_guru_pemohon');
            $table->index(['id_guru_tujuan', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tukar_jadwal');
    }
};
