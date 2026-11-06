@extends('layouts.app')

@section('title', 'Dashboard Waka Kurikulum - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Dashboard Waka Kurikulum</h1>
        <p class="page-subtitle">Pusat kendali pengaturan siklus blok, hak akses pengguna, dan rekap kegiatan KBM</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge-soft badge-soft-primary px-3 py-2">
            <i class="fa-solid fa-arrows-rotate"></i>
            Putaran Aktif: <strong>Putaran {{ $config?->active_cycle ?? '-' }}</strong>
        </span>
        <span class="badge-soft {{ ($config?->is_schedule_locked ?? false) ? 'badge-soft-danger' : 'badge-soft-success' }} px-3 py-2">
            <i class="fa-solid {{ ($config?->is_schedule_locked ?? false) ? 'fa-lock' : 'fa-lock-open' }}"></i>
            Status Jadwal: <strong>{{ ($config?->is_schedule_locked ?? false) ? 'Terkunci' : 'Terbuka' }}</strong>
        </span>
    </div>
</div>
@endsection

@section('content')
<!-- Menu Pintas / Navigasi Cepat -->
<div class="row g-3 mb-4">
    <!-- Card 1: Manajemen Akun -->
    <div class="col-md-6 col-xl-3">
        <div class="feature-card">
            <div class="feature-icon-wrapper bg-primary-subtle text-primary">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <h5 class="fw-bold mb-1">Manajemen Pengguna</h5>
            <p class="text-muted small flex-grow-1">
                Kelola akun pengguna, registrasi guru pengampu, serta penetapan hak akses (Waka, Guru, Siswa).
            </p>
            <div class="pt-2">
                <a href="{{ route('akun.index') }}" class="btn btn-outline-secondary w-100 btn-sm">
                    <i class="fa-solid fa-arrow-right"></i> Buka Manajemen Akun
                </a>
            </div>
        </div>
    </div>

    <!-- Card 2: Rekap Jurnal Mengajar -->
    <div class="col-md-6 col-xl-3">
        <div class="feature-card">
            <div class="feature-icon-wrapper bg-success-subtle text-success">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <h5 class="fw-bold mb-1">Rekap Jurnal Mengajar</h5>
            <p class="text-muted small flex-grow-1">
                Pantau seluruh catatan kegiatan belajar mengajar (KBM) dan presensi dari semua guru pengampu.
            </p>
            <div class="pt-2">
                <a href="{{ route('waka.jurnal.rekap') }}" class="btn btn-outline-secondary w-100 btn-sm">
                    <i class="fa-solid fa-arrow-right"></i> Lihat Rekap KBM
                </a>
            </div>
        </div>
    </div>

    <!-- Card 3: Rekap Absensi Siswa (BK & Waka) -->
    <div class="col-md-6 col-xl-3">
        <div class="feature-card">
            <div class="feature-icon-wrapper bg-warning-subtle text-warning">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <h5 class="fw-bold mb-1">Rekap Absensi Siswa</h5>
            <p class="text-muted small flex-grow-1">
                Pantau siswa tidak masuk, frekuensi ketidakhadiran, catatan alasan, dan pembagian per kelas.
            </p>
            <div class="pt-2">
                <a href="{{ route('rekap.absensi.index') }}" class="btn btn-outline-secondary w-100 btn-sm">
                    <i class="fa-solid fa-arrow-right"></i> Rekap Absensi Siswa
                </a>
            </div>
        </div>
    </div>

    <!-- Card 4: Kelola Jadwal Pelajaran -->
    <div class="col-md-6 col-xl-3">
        <div class="feature-card">
            <div class="feature-icon-wrapper bg-info-subtle text-info">
                <i class="fa-regular fa-calendar-days"></i>
            </div>
            <h5 class="fw-bold mb-1">Jadwal & Anti-Tabrakan</h5>
            <p class="text-muted small flex-grow-1">
                Atur jadwal pelajaran sekolah secara menyeluruh dan lakukan pengecekan jadwal anti-tabrakan.
            </p>
            <div class="pt-2">
                <a href="{{ route('jadwal.index') }}" class="btn btn-outline-secondary w-100 btn-sm">
                    <i class="fa-solid fa-arrow-right"></i> Kelola Jadwal
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Form Konfigurasi Sistem -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title">
                    <i class="fa-solid fa-sliders text-primary"></i>
                    Konfigurasi Siklus Blok & Kunci Jadwal Pelajaran
                </h5>
            </div>
            <div class="app-card-body">
                <form action="{{ route('waka.config.update') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label" for="active_cycle">
                            Status Putaran Blok Aktif <span class="text-danger">*</span>
                        </label>
                        <select id="active_cycle" name="active_cycle" class="form-select" required>
                            <option value="1" {{ ($config?->active_cycle ?? '1') == '1' ? 'selected' : '' }}>
                                Putaran 1 (Siklus Blok Ganjil/Minggu Pertama)
                            </option>
                            <option value="2" {{ ($config?->active_cycle ?? '') == '2' ? 'selected' : '' }}>
                                Putaran 2 (Siklus Blok Genap/Minggu Kedua)
                            </option>
                        </select>
                        <div class="form-text">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Mengubah opsi ini akan menyesuaikan jadwal aktif yang ditampilkan di seluruh portal guru & siswa secara otomatis.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="is_schedule_locked">
                            Status Penguncian Jadwal (Lock Schedule) <span class="text-danger">*</span>
                        </label>
                        <select id="is_schedule_locked" name="is_schedule_locked" class="form-select" required>
                            <option value="0" {{ !($config?->is_schedule_locked ?? false) ? 'selected' : '' }}>
                                &#128275; Buka Akses Edit (Guru diperbolehkan menambah / mengubah jadwal)
                            </option>
                            <option value="1" {{ ($config?->is_schedule_locked ?? false) ? 'selected' : '' }}>
                                &#128274; Kunci Jadwal (Final - Tombol aksi input & edit guru dinonaktifkan)
                            </option>
                        </select>
                        <div class="form-text">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Pilih status "Kunci Jadwal" jika seluruh jadwal pelajaran semester ini sudah selesai dan tidak boleh diubah lagi.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-2 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Konfigurasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection