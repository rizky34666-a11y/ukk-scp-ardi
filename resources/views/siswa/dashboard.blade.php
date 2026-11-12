@extends('layouts.app')

@section('title', 'Dashboard Siswa - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Dashboard Siswa</h1>
        <p class="page-subtitle">
            Halo, <strong>{{ session('nama_lengkap') ?? 'Siswa' }}</strong>.
            @if($siswa?->kelas)
                Kelas Anda: <strong>{{ $siswa->kelas->nama_kelas }}</strong>.
            @endif
        </p>
    </div>
    <a href="{{ route('siswa.izin.index') }}" class="btn btn-outline-primary">
        <i class="fa-solid fa-file-signature me-1"></i> Pengajuan Izin
        @if($izinMenunggu > 0)
            <span class="badge bg-warning text-dark ms-1">{{ $izinMenunggu }} diproses</span>
        @endif
    </a>
</div>
@endsection

@section('content')
@if(!$siswa || !$siswa->id_kelas)
    <div class="app-card">
        <div class="app-card-body text-center py-5 text-muted">
            <i class="fa-solid fa-circle-info fa-2x mb-2 d-block"></i>
            Kelas Anda belum terdaftar di sistem, sehingga jadwal belum dapat ditampilkan. Hubungi wali kelas atau Waka Kurikulum.
        </div>
    </div>
@else
    @include('partials.jadwal-hari-ini', ['jadwalHariIni' => $jadwalHariIni, 'mode' => 'siswa'])
@endif
@endsection
