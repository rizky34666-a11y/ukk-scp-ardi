<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\WakaController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\JadwalImportController;
use App\Http\Controllers\JadwalTabelController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\SiswaIzinController;
use App\Http\Controllers\KesiswaanController;
use App\Http\Controllers\RekapAbsensiController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\GuruIzinController;
use App\Http\Controllers\GuruTukarController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\WakaIzinGuruController;
use App\Http\Controllers\SiswaDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');

Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// ROUTE AUTENTIKASI & LOGOUT
// ==========================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Pendaftaran akun mandiri dinonaktifkan: akun dibuat oleh Waka Kurikulum lewat Manajemen Akun.
Route::redirect('/register', '/login');

// Route Logout yang terhubung dengan tombol logout baru
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Lupa Password & Verifikasi OTP
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('forgot-password.form');
// Dibatasi (throttle) agar kode OTP 6 digit tidak bisa ditebak dengan percobaan beruntun
Route::post('/forgot-password/verify', [AuthController::class, 'verifyIdentity'])->middleware('throttle:10,10')->name('forgot-password.verify');
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtpCode'])->middleware('throttle:10,10')->name('forgot-password.verify-otp');
Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,10')->name('forgot-password.resend-otp');
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
Route::get('/waka/audit-log', [AuditLogController::class, 'index'])->name('waka.audit-log');

// Izin guru: guru mengajukan, hanya Waka yang menyetujui
Route::get('/guru/izin', [GuruIzinController::class, 'index'])->name('guru.izin.index');
Route::post('/guru/izin', [GuruIzinController::class, 'store'])->name('guru.izin.store');
Route::delete('/guru/izin/{id}', [GuruIzinController::class, 'destroy'])->name('guru.izin.destroy');
// Tukar ruangan / tukar jam antar guru (disetujui guru yang bersangkutan)
Route::get('/guru/tukar-jadwal', [GuruTukarController::class, 'index'])->name('guru.tukar.index');
Route::get('/guru/tukar-jadwal/opsi', [GuruTukarController::class, 'opsi'])->name('guru.tukar.opsi');
Route::post('/guru/tukar-jadwal', [GuruTukarController::class, 'store'])->name('guru.tukar.store');
Route::post('/guru/tukar-jadwal/{id}/setujui', [GuruTukarController::class, 'setujui'])->name('guru.tukar.setujui');
Route::post('/guru/tukar-jadwal/{id}/tolak', [GuruTukarController::class, 'tolak'])->name('guru.tukar.tolak');
Route::post('/guru/tukar-jadwal/{id}/batal', [GuruTukarController::class, 'batal'])->name('guru.tukar.batal');
Route::get('/waka/izin-guru', [WakaIzinGuruController::class, 'index'])->name('waka.izin-guru');
Route::post('/waka/izin-guru/{id}', [WakaIzinGuruController::class, 'proses'])->name('waka.izin-guru.proses');


// ==========================================
// MASTER DATA (khusus Waka Kurikulum): siswa, mata pelajaran
// ==========================================
Route::get('/master-data', fn () => redirect()->route('master.index', 'siswa'));
Route::prefix('master-data/{entitas}')->where(['entitas' => 'siswa|mata-pelajaran'])->group(function () {
    Route::get('/', [MasterDataController::class, 'index'])->name('master.index');
    Route::post('/', [MasterDataController::class, 'store'])->name('master.store');
    Route::put('/{id}', [MasterDataController::class, 'update'])->name('master.update')->where('id', '[^/]+');
    Route::delete('/{id}', [MasterDataController::class, 'destroy'])->name('master.destroy')->where('id', '[^/]+');
});

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
Route::get('/tabel-jadwal', [JadwalTabelController::class, 'index'])->name('tabel-jadwal.index');
Route::get('/tabel-jadwal/unduh', [JadwalTabelController::class, 'unduh'])->name('tabel-jadwal.unduh');
Route::get('/tabel-jadwal/unduh-semua', [JadwalTabelController::class, 'unduhSemua'])->name('tabel-jadwal.unduh-semua');
Route::get('/jadwal', [JadwalPelajaranController::class, 'index'])->name('jadwal.index');
Route::post('/jadwal/store', [JadwalPelajaranController::class, 'store'])->name('jadwal.store');
Route::post('/jadwal/config/update', [JadwalPelajaranController::class, 'updateConfig'])->name('jadwal.config.update');
Route::get('/jadwal/import/template', [JadwalImportController::class, 'template'])->name('jadwal.import.template');
Route::post('/jadwal/import/periksa', [JadwalImportController::class, 'periksa'])->name('jadwal.import.periksa');
Route::get('/jadwal/import/pratinjau', [JadwalImportController::class, 'pratinjau'])->name('jadwal.import.pratinjau');
Route::post('/jadwal/import/simpan', [JadwalImportController::class, 'simpan'])->name('jadwal.import.simpan');
Route::post('/jadwal/import/batal', [JadwalImportController::class, 'batal'])->name('jadwal.import.batal');


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
Route::get('/siswa/dashboard', [SiswaDashboardController::class, 'index'])->name('siswa.dashboard');
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
