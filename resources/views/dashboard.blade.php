@extends('layouts.app')

@section('title', 'Dashboard Utama - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Sistem Informasi Akademik UKK SCP</h1>
        <p class="page-subtitle">Sistem Penjadwalan Pelajaran, Anti-Tabrakan, & Jurnal Mengajar Guru</p>
    </div>
</div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="app-card text-center p-4 p-md-5">
            <div class="auth-brand-badge mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h3 class="fw-bold text-dark mb-2">Selamat Datang di Portal UKK SCP</h3>
            <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                Platform terpadu untuk pengaturan jadwal pelajaran bebas bentrok, pencatatan kegiatan belajar mengajar harian guru, dan pengajuan surat izin siswa.
            </p>

            <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                <a href="{{ route('jadwal.index') }}" class="btn btn-primary px-4 py-2">
                    <i class="fa-regular fa-calendar-days"></i> Kelola Jadwal Pelajaran
                </a>
                <a href="{{ route('jurnal.index') }}" class="btn btn-success px-4 py-2">
                    <i class="fa-solid fa-book-bookmark"></i> Modul Jurnal Mengajar
                </a>
            </div>
        </div>
    </div>
</div>
@endsection