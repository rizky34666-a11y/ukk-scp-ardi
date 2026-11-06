<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\WakaController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\SiswaIzinController;
use App\Http\Controllers\KesiswaanController;
use App\Http\Controllers\RekapAbsensiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// ROUTE AUTENTIKASI & LOGOUT
// ==========================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/register-process', [AuthController::class, 'register'])->name('register.post');

// Route Logout yang terhubung dengan tombol logout baru
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Lupa Password & Verifikasi OTP
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('forgot-password.form');
Route::post('/forgot-password/verify', [AuthController::class, 'verifyIdentity'])->name('forgot-password.verify');
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtpCode'])->name('forgot-password.verify-otp');
Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendOtp'])->name('forgot-password.resend-otp');
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword'])->name('forgot-password.reset');

// Ganti Password dengan OTP untuk Pengguna yang Sedang Login
Route::post('/profil/kirim-otp', [AuthController::class, 'sendProfileOtp'])->name('profile.send-otp');
Route::post('/profil/ganti-password', [AuthController::class, 'updateProfilePassword'])->name('profile.update-password');

// Halaman Profil (foto, email, no. WhatsApp) — nama & NIP/NISN hanya bisa diubah Waka via Manajemen Akun
Route::get('/profil', [ProfilController::class, 'index'])->name('profile.index');
Route::post('/profil', [ProfilController::class, 'update'])->name('profile.update');
Route::post('/profil/foto/hapus', [ProfilController::class, 'hapusFoto'])->name('profile.photo.delete');



// ==========================================
// ROUTE WAKA KURIKULUM
// ==========================================
Route::get('/waka/dashboard', [WakaController::class, 'dashboard'])->name('waka.dashboard');
Route::post('/waka/config/update', [WakaController::class, 'updateConfig'])->name('waka.config.update');
Route::get('/waka/jurnal/rekap', [JurnalController::class, 'rekapWaka'])->name('waka.jurnal.rekap');


// ==========================================
// ROUTE MANAJEMEN AKUN & ROLE
// ==========================================
Route::get('/manajemen-akun', [JadwalPelajaranController::class, 'indexAkun'])->name('akun.index');
Route::post('/manajemen-akun/store', [JadwalPelajaranController::class, 'storeAkun'])->name('akun.store');
Route::post('/manajemen-akun/import', [JadwalPelajaranController::class, 'importAkun'])->name('akun.import');
Route::get('/manajemen-akun/template', [JadwalPelajaranController::class, 'downloadTemplate'])->name('akun.template');
Route::get('/manajemen-akun/template-siswa', [JadwalPelajaranController::class, 'downloadTemplateSiswa'])->name('akun.template.siswa');
Route::put('/manajemen-akun/{id}/update', [JadwalPelajaranController::class, 'updateAkun'])->name('akun.update');
Route::post('/manajemen-akun/{id}/update', [JadwalPelajaranController::class, 'updateAkun'])->name('akun.update.post');
Route::delete('/manajemen-akun/{id}/delete', [JadwalPelajaranController::class, 'deleteAkun'])->name('akun.delete');
Route::post('/manajemen-akun/{id}/delete', [JadwalPelajaranController::class, 'deleteAkun'])->name('akun.delete.post');
Route::post('/manajemen-akun/bulk-delete', [JadwalPelajaranController::class, 'bulkDeleteAkun'])->name('akun.bulkDelete');
Route::delete('/manajemen-akun/bulk-delete', [JadwalPelajaranController::class, 'bulkDeleteAkun'])->name('akun.bulkDelete.delete');


// ==========================================
// ROUTE GURU
// ==========================================
Route::get('/guru/dashboard', [GuruController::class, 'dashboard'])->name('guru.dashboard');


// ==========================================
// ROUTE JADWAL
// ==========================================
Route::get('/jadwal', [JadwalPelajaranController::class, 'index'])->name('jadwal.index');
Route::post('/jadwal/store', [JadwalPelajaranController::class, 'store'])->name('jadwal.store');
Route::post('/jadwal/config/update', [JadwalPelajaranController::class, 'updateConfig'])->name('jadwal.config.update');


// ==========================================
// ROUTE JURNAL
// ==========================================
Route::get('/jurnal', [JurnalController::class, 'index'])->name('jurnal.index');
Route::post('/jurnal/store', [JurnalController::class, 'store'])->name('jurnal.store');
Route::put('/jurnal/{id}', [JurnalController::class, 'update'])->name('jurnal.update');
Route::get('/jurnal/get-siswa', [JurnalController::class, 'getSiswaByJadwal'])->name('jurnal.getSiswa');


// ==========================================
// ROUTE SISWA (PENGAJUAN IZIN / SAKIT)
// ==========================================
Route::get('/siswa/izin', [SiswaIzinController::class, 'index'])->name('siswa.izin.index');
Route::post('/siswa/izin', [SiswaIzinController::class, 'store'])->name('siswa.izin.store');


// ==========================================
// ROUTE KESISWAAN (WALI KELAS & GURU BK) - VERIFIKASI IZIN
// ==========================================
Route::get('/kesiswaan/izin', [KesiswaanController::class, 'index'])->name('kesiswaan.izin.index');
Route::post('/kesiswaan/izin/{id}/update', [KesiswaanController::class, 'updateStatus'])->name('kesiswaan.izin.update');


// ==========================================
// ROUTE REKAP ABSENSI SISWA TIDAK BERANGKAT (BK & WAKA)
// ==========================================
Route::get('/rekap/absensi', [RekapAbsensiController::class, 'index'])->name('rekap.absensi.index');
