<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('izin_guru')) {
            return;
        }

        Schema::create('izin_guru', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_guru')->index();
            $table->unsignedInteger('id_user')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('jenis', 40);
            $table->text('alasan');
            $table->string('lampiran')->nullable();
            $table->text('tugas_pengganti')->nullable();
            $table->enum('status', ['Menunggu', 'Disetujui', 'Ditolak'])->default('Menunggu')->index();
            $table->text('catatan_waka')->nullable();
            $table->unsignedInteger('diproses_oleh')->nullable();
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_guru');
    }
};
