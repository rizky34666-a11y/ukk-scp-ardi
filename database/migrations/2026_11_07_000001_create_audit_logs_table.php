<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_user')->nullable()->index();
            $table->string('username', 50)->nullable()->index();
            $table->string('nama_lengkap', 100)->nullable();
            $table->unsignedInteger('id_role')->nullable();
            $table->string('aksi', 40)->index();          // login, login_gagal, tambah, ubah, hapus, ...
            $table->string('modul', 40)->index();         // auth, jadwal, jurnal, izin, akun, konfigurasi
            $table->string('target_id', 40)->nullable();
            $table->text('deskripsi')->nullable();
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
