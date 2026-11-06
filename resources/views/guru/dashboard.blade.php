@extends('layouts.app')

@php
    $isWaliKelas = (session('id_role') == 3);
@endphp

@section('title', ($isWaliKelas ? 'Dashboard Wali Kelas & Guru' : 'Dashboard Guru') . ' - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">{{ $isWaliKelas ? 'Dashboard Wali Kelas & Guru' : 'Dashboard Guru Pengampu' }}</h1>
        <p class="page-subtitle">
            Selamat datang kembali, <strong>{{ session('nama_lengkap') ?? 'Bapak/Ibu Guru' }}</strong>. 
            {{ $isWaliKelas ? 'Anda memiliki akses ganda sebagai Guru Pengampu KBM dan Wali Kelas.' : 'Silakan pilih modul kerja harian Anda.' }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        @if($isWaliKelas)
            <span class="badge-soft badge-soft-info px-3 py-2">
                <i class="fa-solid fa-user-shield me-1"></i> Peran Ganda: Wali Kelas & Guru
            </span>
        @endif
        <span class="badge-soft badge-soft-primary px-3 py-2">
            <i class="fa-regular fa-calendar-check me-1"></i>
            {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
        </span>
    </div>
</div>
@endsection

@section('content')
<div class="row g-4 justify-content-center">
    <!-- Modul 1: Jurnal Mengajar -->
    <div class="col-md-6 {{ $isWaliKelas ? 'col-lg-6' : 'col-lg-5' }}">
        <div class="feature-card p-4 {{ (isset($overdueJadwals) && $overdueJadwals->isNotEmpty()) ? 'border border-danger border-2' : '' }}">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="feature-icon-wrapper {{ (isset($overdueJadwals) && $overdueJadwals->isNotEmpty()) ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' }} m-0">
                    <i class="fa-solid fa-book-bookmark"></i>
                </div>
                @if(isset($overdueJadwals) && $overdueJadwals->isNotEmpty())
                    <span class="badge bg-danger text-white px-3 py-2 rounded-pill shadow-xs">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $overdueJadwals->count() }} Belum Diisi (>30 Menit KBM Dimulai)
                    </span>
                @endif
            </div>
            <h4 class="fw-bold mb-2">Modul Jurnal Mengajar</h4>
            <p class="text-muted small flex-grow-1">
                Catat kegiatan belajar mengajar (KBM) harian secara akurat, presensi ketidakhadiran siswa di jam KBM Anda, dan unggah foto dokumentasi kelas.
            </p>
            <div class="pt-3 border-top mt-2">
                @if(isset($overdueJadwals) && $overdueJadwals->isNotEmpty())
                    <a href="{{ route('jurnal.index', ['schedule_id' => $overdueJadwals->first()->id_jadwal]) }}" class="btn btn-danger w-100 py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Isi Jurnal Sekarang ({{ $overdueJadwals->count() }} Terlambat)
                    </a>
                @else
                    <a href="{{ route('jurnal.index') }}" class="btn btn-primary w-100 py-2">
                        <i class="fa-solid fa-arrow-right"></i> Buka Jurnal Mengajar
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Modul 2: Jadwal Pelajaran -->
    <div class="col-md-6 {{ $isWaliKelas ? 'col-lg-6' : 'col-lg-5' }}">
        <div class="feature-card p-4">
            <div class="feature-icon-wrapper bg-success-subtle text-success">
                <i class="fa-regular fa-calendar-days"></i>
            </div>
            <h4 class="fw-bold mb-2">Jadwal Mengajar</h4>
            <p class="text-muted small flex-grow-1">
                Lihat dan input jadwal mengajar Anda per kelas dan ruangan, pantau putaran siklus blok aktif, serta kelola jam pelajaran Anda.
            </p>
            <div class="pt-3 border-top mt-2">
                <a href="{{ route('jadwal.index') }}" class="btn btn-success w-100 py-2">
                    <i class="fa-solid fa-arrow-right"></i> Kelola Jadwal Pelajaran
                </a>
            </div>
        </div>
    </div>

    @if($isWaliKelas)
        @php
            $pendingIzin = \App\Models\LeaveRequest::where('status_approval', 'Pending')->count();
        @endphp
        <!-- Modul 3 (Khusus Wali Kelas): Verifikasi Izin Siswa -->
        <div class="col-md-6 col-lg-6">
            <div class="feature-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="feature-icon-wrapper bg-warning-subtle text-warning m-0">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    @if($pendingIzin > 0)
                        <span class="badge bg-danger text-white px-3 py-2 rounded-pill shadow-xs">
                            <i class="fa-solid fa-bell me-1"></i> {{ $pendingIzin }} Menunggu Verifikasi
                        </span>
                    @else
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                            <i class="fa-solid fa-check me-1"></i> Semua Terverifikasi
                        </span>
                    @endif
                </div>
                <h4 class="fw-bold mb-2">Verifikasi Izin Siswa</h4>
                <p class="text-muted small flex-grow-1">
                    Tinjau surat dan permohonan izin atau sakit dari siswa, serta berikan persetujuan atau penolakan dengan cepat.
                </p>
                <div class="pt-3 border-top mt-2">
                    <a href="{{ route('kesiswaan.izin.index') }}" class="btn btn-warning w-100 py-2 fw-semibold text-dark">
                        <i class="fa-solid fa-arrow-right"></i> Buka Verifikasi Izin ({{ $pendingIzin }} Baru)
                    </a>
                </div>
            </div>
        </div>

        <!-- Modul 4 (Khusus Wali Kelas): Rekap Absensi Siswa -->
        <div class="col-md-6 col-lg-6">
            <div class="feature-card p-4">
                <div class="feature-icon-wrapper bg-info-subtle text-info">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>
                <h4 class="fw-bold mb-2">Rekap Absensi Siswa</h4>
                <p class="text-muted small flex-grow-1">
                    Pantau akumulasi ketidakhadiran siswa (Sakit, Izin, Alfa) per kelas secara terpadu untuk monitoring kedisiplinan siswa.
                </p>
                <div class="pt-3 border-top mt-2">
                    <a href="{{ route('rekap.absensi.index') }}" class="btn btn-outline-primary w-100 py-2">
                        <i class="fa-solid fa-arrow-right"></i> Buka Rekap Absensi
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Informasi Pengingat Singkat -->
<div class="row mt-4 justify-content-center">
    <div class="col-12">
        <div class="app-card mb-0">
            <div class="app-card-body d-flex align-items-center gap-3">
                <div class="p-3 bg-light rounded text-muted fs-4">
                    <i class="fa-solid fa-lightbulb text-warning"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Catatan Penting Guru & Wali Kelas:</h6>
                    <p class="text-muted small mb-0">
                        Pastikan untuk mengisi jurnal mengajar setelah setiap sesi KBM berakhir dengan menyertakan foto dokumentasi kelas yang jelas. Siswa yang izinnya telah disetujui otomatis akan ditandai pada form presensi saat Anda mengisi jurnal.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
