@extends('layouts.app')

@section('title', 'Rekapitulasi Absensi Siswa Tidak Berangkat - BK & Waka - UKK SCP')

@push('styles')
<style>
    /* Styling Khusus Rekap Absensi */
    .kelas-header-card {
        background: linear-gradient(135deg, #0f2a5c 0%, #1e3a6e 100%);
        color: #ffffff;
        border-radius: var(--radius) var(--radius) 0 0;
        padding: 1rem 1.25rem;
    }

    .badge-count {
        font-size: 0.78rem;
        font-weight: 700;
        padding: 0.35rem 0.65rem;
        border-radius: 6px;
    }

    .history-badge {
        font-size: 0.72rem;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-weight: 600;
    }

    .reason-box {
        background-color: var(--page-bg-soft);
        border-left: 3px solid var(--accent-blue);
        border-radius: 4px;
        padding: 0.45rem 0.7rem;
        font-size: 0.83rem;
        margin-top: 0.25rem;
    }

    .reason-box-alfa {
        background-color: #fef2f2;
        border-left: 3px solid #dc2626;
        border-radius: 4px;
        padding: 0.45rem 0.7rem;
        font-size: 0.83rem;
        margin-top: 0.25rem;
        color: #991b1b;
    }

    .pulse-badge {
        animation: pulseDanger 1.8s infinite;
    }

    @keyframes pulseDanger {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.03); opacity: 0.88; }
        100% { transform: scale(1); opacity: 1; }
    }

    .period-btn {
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.35rem 0.85rem;
        transition: all 0.2s ease;
    }

    .period-btn.active {
        background-color: var(--color-dark);
        color: #ffffff;
        border-color: var(--color-dark);
        box-shadow: 0 2px 6px rgba(15, 42, 92, 0.25);
    }

    /* Print styling */
    @media print {
        .app-sidebar, .app-topbar, .filter-card, .btn-print, .btn-back, .page-nav-pill, .modal, .quick-period-bar {
            display: none !important;
        }
        .app-main {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .app-card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            page-break-inside: avoid;
            margin-bottom: 2rem !important;
        }
        .kelas-header-card {
            background: #222 !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@endpush

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-clipboard-user text-primary me-2"></i>
            Rekap Absensi Siswa Tidak Berangkat
        </h1>
        <p class="page-subtitle">
            Monitoring terpadu ketidakhadiran siswa: memadukan <strong>Pengajuan Izin Siswa</strong> (Wali Kelas & Guru BK) dan <strong>Siswa Alfa di Jurnal Guru</strong>, deteksi <strong>Alfa berturut-turut</strong>, dan pembagian per kelas.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm btn-print">
            <i class="fa-solid fa-print me-1"></i> Cetak Rekap
        </button>
        @if(session('id_role') == 4)
            <a href="{{ route('waka.dashboard') }}" class="btn btn-outline-primary btn-sm btn-back">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard Waka
            </a>
        @elseif(session('id_role') == 7 || session('id_role') == 3)
            <a href="{{ route('kesiswaan.izin.index') }}" class="btn btn-outline-primary btn-sm btn-back">
                <i class="fa-solid fa-arrow-left me-1"></i> Verifikasi Izin
            </a>
        @endif
    </div>
</div>
@endsection

@section('content')

{{-- 1. Kartu Ringkasan Metrik Statistik --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card h-100">
            <div class="stat-icon tone-blue">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalSiswaUnik }}</div>
                <div class="stat-label">Siswa Tidak Masuk</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card h-100 border-danger">
            <div class="stat-icon" style="background-color: #fee2e2; color: #dc2626;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="stat-value text-danger">{{ $totalSiswaConsecutiveAlfa }}</div>
                <div class="stat-label fw-bold text-danger">Alfa Berturut-turut</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card h-100">
            <div class="stat-icon" style="background-color: #fef2f2; color: #b91c1c;">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div>
                <div class="stat-value text-danger">{{ $totalFrekuensiAlfa }}</div>
                <div class="stat-label">Total Kali Alfa</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card h-100">
            <div class="stat-icon" style="background-color: var(--accent-green-soft); color: var(--accent-green);">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalFrekuensiIzinSakit }}</div>
                <div class="stat-label">Izin / Sakit (Surat)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card h-100">
            <div class="stat-icon" style="background-color: #fef3c7; color: #d97706;">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalTotalTidakMasuk }}</div>
                <div class="stat-label">Akumulasi Absen</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card h-100">
            <div class="stat-icon" style="background-color: #fee2e2; color: #b91c1c;">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalPerluTindakanBK }}</div>
                <div class="stat-label">Perhatian Khusus BK</div>
            </div>
        </div>
    </div>
</div>

{{-- 2. Bar Tombol Pintas Filter Periode Cepat & Form Pencarian --}}
<div class="app-card mb-4 filter-card">
    <div class="app-card-body p-3">
        {{-- Quick Period Filter Buttons --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-3 border-bottom quick-period-bar">
            <div class="d-flex flex-wrap align-items-center gap-1">
                <span class="small fw-bold text-muted me-2">
                    <i class="fa-regular fa-calendar-check text-primary me-1"></i> Periode Cepat:
                </span>
                <a href="{{ route('rekap.absensi.index', array_merge(request()->except(['period', 'start_date', 'end_date']), ['period' => 'all'])) }}" 
                   class="btn btn-outline-secondary period-btn {{ $period === 'all' ? 'active' : '' }}">
                    Semua Waktu
                </a>
                <a href="{{ route('rekap.absensi.index', array_merge(request()->except(['period', 'start_date', 'end_date']), ['period' => 'today'])) }}" 
                   class="btn btn-outline-secondary period-btn {{ $period === 'today' ? 'active' : '' }}">
                    <i class="fa-solid fa-sun me-1 text-warning"></i> Hari Ini
                </a>
                <a href="{{ route('rekap.absensi.index', array_merge(request()->except(['period', 'start_date', 'end_date']), ['period' => 'this_week'])) }}" 
                   class="btn btn-outline-secondary period-btn {{ $period === 'this_week' ? 'active' : '' }}">
                    <i class="fa-regular fa-calendar me-1 text-info"></i> Minggu Ini
                </a>
                <a href="{{ route('rekap.absensi.index', array_merge(request()->except(['period', 'start_date', 'end_date']), ['period' => 'this_month'])) }}" 
                   class="btn btn-outline-secondary period-btn {{ $period === 'this_month' ? 'active' : '' }}">
                    <i class="fa-regular fa-calendar-days me-1 text-success"></i> Bulan Ini
                </a>
            </div>

            @if($startDate || $endDate)
                <div class="small text-muted">
                    <i class="fa-solid fa-clock me-1 text-primary"></i>
                    Rentang Aktif: <strong>{{ $startDate ? \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') : 'Awal' }}</strong> s/d <strong>{{ $endDate ? \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') : 'Sekarang' }}</strong>
                </div>
            @endif
        </div>

        {{-- Filter Utama Form --}}
        <form method="GET" action="{{ route('rekap.absensi.index') }}" class="row g-2 align-items-end">
            <!-- Filter Kelas -->
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="form-label small fw-semibold text-muted mb-1" for="filter_kelas">
                    <i class="fa-solid fa-school me-1"></i> Pilih Kelas
                </label>
                <select name="kelas" id="filter_kelas" class="form-select form-select-sm">
                    <option value="all" {{ $filterKelas === 'all' ? 'selected' : '' }}>-- Semua Kelas --</option>
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id_kelas }}" {{ $filterKelas === $k->id_kelas ? 'selected' : '' }}>
                            {{ $k->tingkat ?? '' }} {{ $k->jurusan?->nama_jurusan ?? $k->id_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status -->
            <div class="col-6 col-sm-6 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="filter_status">
                    <i class="fa-solid fa-filter me-1"></i> Kategori Absen
                </label>
                <select name="status" id="filter_status" class="form-select form-select-sm">
                    <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>Semua (Izin & Alfa)</option>
                    <option value="Alfa" {{ $filterStatus === 'Alfa' ? 'selected' : '' }}>Hanya Alfa di Jurnal Guru</option>
                    <option value="Approved" {{ $filterStatus === 'Approved' ? 'selected' : '' }}>Hanya Izin Disetujui (Approved)</option>
                    <option value="Pending" {{ $filterStatus === 'Pending' ? 'selected' : '' }}>Izin Menunggu (Pending)</option>
                    <option value="Rejected" {{ $filterStatus === 'Rejected' ? 'selected' : '' }}>Izin Ditolak (Rejected)</option>
                </select>
            </div>

            <!-- Rentang Tanggal Mulai -->
            <div class="col-6 col-sm-6 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="start_date">
                    <i class="fa-regular fa-calendar me-1"></i> Dari Tanggal
                </label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>

            <!-- Rentang Tanggal Selesai -->
            <div class="col-6 col-sm-6 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="end_date">
                    <i class="fa-regular fa-calendar me-1"></i> Sampai Tanggal
                </label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>

            <!-- Pencarian Siswa -->
            <div class="col-6 col-sm-6 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="search">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari Siswa / Alasan
                </label>
                <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Nama / NISN / alasan..." value="{{ $search }}">
            </div>

            <!-- Tombol Aksi -->
            <div class="col-12 col-sm-6 col-lg-1 d-flex gap-1 align-self-end">
                <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Terapkan Filter">
                    <i class="fa-solid fa-filter"></i><span class="d-lg-none ms-1">Filter</span>
                </button>
                <a href="{{ route('rekap.absensi.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i><span class="d-lg-none ms-1">Reset</span>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Navigasi Pintas Pill Kelas --}}
@if(count($kelasGroups) > 1)
<div class="d-flex flex-wrap gap-2 mb-4 page-nav-pill align-items-center">
    <span class="small fw-bold text-muted me-1">
        <i class="fa-solid fa-layer-group me-1"></i> Lompat ke Kelas:
    </span>
    @foreach($kelasGroups as $kId => $kData)
        <a href="#kelas-{{ Str::slug($kId) }}" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 0.78rem;">
            {{ $kData['nama_kelas'] }}
            <span class="badge bg-danger rounded-pill ms-1">{{ count($kData['siswa']) }}</span>
            @if(!empty($kData['total_siswa_consecutive_alfa']) && $kData['total_siswa_consecutive_alfa'] > 0)
                <span class="badge bg-danger rounded-pill ms-1 pulse-badge" title="{{ $kData['total_siswa_consecutive_alfa'] }} siswa Alfa berturut-turut">
                    <i class="fa-solid fa-triangle-exclamation"></i> {{ $kData['total_siswa_consecutive_alfa'] }}
                </span>
            @endif
        </a>
    @endforeach
</div>
@endif

{{-- 3. Data Rekap Absensi Terbagi Menjadi Setiap Kelas --}}
@if(count($kelasGroups) === 0)
    <div class="app-card py-5 text-center">
        <div class="py-4">
            <i class="fa-solid fa-user-check fa-3x text-success mb-3 d-block"></i>
            <h5 class="fw-bold text-dark">Tidak Ada Data Siswa Tidak Masuk</h5>
            <p class="text-muted small mb-0">
                @if($filterKelas !== 'all' || $filterStatus !== 'all' || $startDate || $endDate || $search || $period !== 'all')
                    Tidak ditemukan data siswa izin atau alfa dengan kriteria filter yang diterapkan.
                @else
                    Semua siswa tercatat hadir dengan baik atau belum ada pengajuan izin / data alfa pada jurnal KBM.
                @endif
            </p>
        </div>
    </div>
@else
    @foreach($kelasGroups as $kId => $kData)
        <div class="app-card mb-4" id="kelas-{{ Str::slug($kId) }}">
            {{-- Header Kelas --}}
            <div class="kelas-header-card d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-dark p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-graduation-cap text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-white">{{ $kData['nama_kelas'] }}</h5>
                        <small class="text-white-50">
                            {{ $kData['tingkat'] ? 'Tingkat ' . $kData['tingkat'] : '' }} 
                            {{ $kData['jurusan'] ? '• ' . $kData['jurusan'] : '' }}
                        </small>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-light text-dark badge-count">
                        <i class="fa-solid fa-users me-1 text-primary"></i>
                        {{ count($kData['siswa']) }} Siswa Tidak Hadir
                    </span>
                    @if(!empty($kData['total_siswa_consecutive_alfa']) && $kData['total_siswa_consecutive_alfa'] > 0)
                        <span class="badge bg-danger text-white badge-count pulse-badge">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            {{ $kData['total_siswa_consecutive_alfa'] }} Siswa Alfa Berturut-turut!
                        </span>
                    @endif
                    <span class="badge bg-warning text-dark badge-count">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i>
                        {{ $kData['total_tidak_hadir'] }} Kali Total Ketidakhadiran
                    </span>
                    @if($kData['total_alfa'] > 0)
                        <span class="badge bg-danger text-white history-badge">
                            <i class="fa-solid fa-user-xmark me-1"></i> Alfa: {{ $kData['total_alfa'] }}
                        </span>
                    @endif
                    @if($kData['total_izin'] > 0)
                        <span class="badge bg-primary text-white history-badge">
                            <i class="fa-solid fa-envelope-open-text me-1"></i> Izin: {{ $kData['total_izin'] }}
                        </span>
                    @endif
                    @if($kData['total_sakit'] > 0)
                        <span class="badge bg-info text-white history-badge">
                            <i class="fa-solid fa-briefcase-medical me-1"></i> Sakit: {{ $kData['total_sakit'] }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Tabel Siswa dalam Kelas Ini --}}
            <div class="app-card-body p-0">
                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 45px;">No</th>
                                <th style="min-width: 190px;">Identitas Siswa</th>
                                <th class="text-center" style="width: 140px;">Frekuensi Tidak Masuk</th>
                                <th class="text-center" style="width: 190px;">Rincian Ketidakhadiran</th>
                                <th style="min-width: 290px;">Alasan & Riwayat Ketidakhadiran Terakhir</th>
                                <th class="text-center" style="width: 175px;">Evaluasi & Tindakan BK</th>
                                <th class="text-center" style="width: 85px;">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @foreach($kData['siswa'] as $sId => $s)
                                <tr>
                                    {{-- No Urut --}}
                                    <td class="text-center text-muted fw-bold">{{ $no++ }}</td>

                                    {{-- Identitas Siswa & Peringatan Alfa Berturut-turut --}}
                                    <td>
                                        <div class="fw-bold text-dark">{{ $s['nama_siswa'] }}</div>
                                        <small class="text-muted font-monospace">NISN: {{ $s['nisn'] }}</small>
                                        
                                        {{-- Tanda Notifikasi Alfa Berturut-turut --}}
                                        @if(!empty($s['has_consecutive_alfa']))
                                            <div class="mt-1">
                                                <span class="badge bg-danger text-white py-1 px-2 pulse-badge shadow-sm" style="font-size: 0.7rem;">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                    Alfa {{ $s['max_consecutive_alfa'] }} Hari Berturut-turut!
                                                </span>
                                            </div>
                                        @elseif($s['alfa_count'] > 0)
                                            <div class="mt-1">
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;">
                                                    <i class="fa-solid fa-user-xmark me-1"></i> Tercatat Alfa di Jurnal Guru
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Keterangan Tidak Berangkat Berapa Kali --}}
                                    <td class="text-center">
                                        <div class="fw-bold text-danger fs-6">
                                            {{ $s['total_tidak_hadir'] }} <span class="fs-7 fw-semibold text-muted">Kali</span>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.72rem;">Tidak Berangkat</small>
                                    </td>

                                    {{-- Rincian Frekuensi Alfa, Izin, Sakit --}}
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                                            @if($s['alfa_count'] > 0)
                                                <span class="badge bg-danger text-white history-badge" title="Alfa di Jurnal Guru">
                                                    <i class="fa-solid fa-user-xmark me-1"></i>Alfa: <strong>{{ $s['alfa_count'] }}</strong>
                                                </span>
                                            @endif
                                            @if($s['izin_count'] > 0)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle history-badge" title="Izin (Surat)">
                                                    <i class="fa-solid fa-envelope-open-text me-1"></i>Izin: <strong>{{ $s['izin_count'] }}</strong>
                                                </span>
                                            @endif
                                            @if($s['sakit_count'] > 0)
                                                <span class="badge bg-info-subtle text-info border border-info-subtle history-badge" title="Sakit">
                                                    <i class="fa-solid fa-briefcase-medical me-1"></i>Sakit: <strong>{{ $s['sakit_count'] }}</strong>
                                                </span>
                                            @endif
                                            @if($s['alfa_count'] == 0 && $s['izin_count'] == 0 && $s['sakit_count'] == 0)
                                                <span class="badge bg-secondary text-white history-badge">0 Kali</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Alasan Tidak Berangkat --}}
                                    <td>
                                        @if(count($s['riwayat']) > 0)
                                            @php
                                                $latest = $s['riwayat'][0];
                                                $isAlfa = ($latest['status'] === 'Alfa' || $latest['tipe'] === 'Alfa (Jurnal Guru)');
                                            @endphp
                                            <div>
                                                <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
                                                    @if($isAlfa)
                                                        <span class="badge bg-danger" style="font-size: 0.65rem;">
                                                            <i class="fa-solid fa-user-xmark me-1"></i> Alfa (Jurnal KBM)
                                                        </span>
                                                    @elseif($latest['status_approval'] === 'Approved')
                                                        <span class="badge bg-success" style="font-size: 0.65rem;">
                                                            <i class="fa-solid fa-check me-1"></i> Izin Disetujui
                                                        </span>
                                                    @elseif($latest['status_approval'] === 'Pending')
                                                        <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">
                                                            <i class="fa-solid fa-clock me-1"></i> Menunggu
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger" style="font-size: 0.65rem;">
                                                            <i class="fa-solid fa-xmark me-1"></i> Ditolak
                                                        </span>
                                                    @endif

                                                    <small class="text-muted fw-semibold" style="font-size: 0.75rem;">
                                                        <i class="fa-regular fa-calendar me-1"></i>
                                                        {{ $latest['start_date'] ? \Carbon\Carbon::parse($latest['start_date'])->translatedFormat('d M Y') : '-' }}
                                                        @if(!empty($latest['end_date']) && $latest['start_date'] !== $latest['end_date'])
                                                            s/d {{ \Carbon\Carbon::parse($latest['end_date'])->translatedFormat('d M Y') }}
                                                        @endif
                                                    </small>
                                                </div>

                                                <div class="{{ $isAlfa ? 'reason-box-alfa' : 'reason-box' }}">
                                                    <strong>Alasan:</strong> {{ $latest['alasan'] }}
                                                </div>

                                                @if(!empty($latest['verified_by']))
                                                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                                        <i class="fa-solid {{ $isAlfa ? 'fa-chalkboard-user text-danger' : 'fa-user-check text-success' }} me-1"></i>
                                                        {{ $latest['verified_by'] }}
                                                    </small>
                                                @endif
                                            </div>

                                            @if(count($s['riwayat']) > 1)
                                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                                    <i class="fa-solid fa-plus-circle me-1"></i>
                                                    {{ count($s['riwayat']) - 1 }} riwayat ketidakhadiran lainnya
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    {{-- Status Evaluasi BK --}}
                                    <td class="text-center">
                                        @if(!empty($s['has_consecutive_alfa']))
                                            <span class="badge bg-danger text-white py-1 px-2 shadow-sm d-block mb-1" style="font-size: 0.7rem;">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                {{ $s['max_consecutive_alfa'] >= 3 ? 'Home Visit BK / Pemanggilan' : 'Peringatan Dini BK' }}
                                            </span>
                                        @elseif($s['warna_bk'] === 'danger')
                                            <span class="badge bg-danger text-white py-1 px-2" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                Panggilan Ortu / Kasus Berat
                                            </span>
                                        @elseif($s['warna_bk'] === 'warning')
                                            <span class="badge bg-warning text-dark py-1 px-2" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-eye me-1"></i>
                                                Perhatian Khusus BK
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-check me-1"></i>
                                                Pemantauan Normal
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Tombol Modal Rincian Lengkap --}}
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-outline-primary btn-sm py-1 px-2" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalDetailSiswa{{ Str::slug($kId) }}_{{ $s['id_siswa'] }}"
                                                title="Lihat Seluruh Riwayat Tidak Masuk & Alasan">
                                            <i class="fa-solid fa-list-ul"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@endif

{{-- 4. Modals Detail Seluruh Riwayat Ketidakhadiran Per Siswa (Izin & Alfa) --}}
@foreach($kelasGroups as $kId => $kData)
    @foreach($kData['siswa'] as $sId => $s)
        <div class="modal fade" id="modalDetailSiswa{{ Str::slug($kId) }}_{{ $s['id_siswa'] }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="border-radius: var(--radius); overflow: hidden;">
                    {{-- Header Modal --}}
                    <div class="modal-header" style="background: var(--color-dark); color: #fff; padding: 0.9rem 1.25rem;">
                        <div>
                            <h6 class="modal-title mb-0 fw-bold">
                                <i class="fa-solid fa-user-clock me-2 text-warning"></i>
                                Riwayat Lengkap Ketidakhadiran Siswa
                            </h6>
                            <small class="text-white-50">
                                {{ $s['nama_siswa'] }} (NISN: {{ $s['nisn'] }}) — {{ $kData['nama_kelas'] }}
                            </small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    {{-- Body Modal --}}
                    <div class="modal-body p-0">
                        {{-- Notifikasi Khusus Jika Siswa Alfa Berturut-turut --}}
                        @if(!empty($s['has_consecutive_alfa']))
                            <div class="p-3 bg-danger-subtle border-bottom border-danger d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-danger text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                    <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-danger fs-6">
                                        Peringatan: Siswa Tercatat Alfa {{ $s['max_consecutive_alfa'] }} Hari Berturut-turut!
                                    </div>
                                    <small class="text-danger-emphasis">
                                        Siswa ini tidak masuk sekolah tanpa keterangan secara berturut-turut pada hari KBM. Disarankan Guru BK segera memanggil orang tua / wali atau berkoordinasi untuk kunjungan rumah (<em>home visit</em>).
                                    </small>
                                </div>
                            </div>
                        @endif

                        {{-- Ringkasan Frekuensi Siswa --}}
                        <div class="p-3 bg-light border-bottom">
                            <div class="row g-2 text-center">
                                <div class="col-3">
                                    <div class="p-2 bg-white rounded border">
                                        <div class="small text-muted">Total Tidak Hadir</div>
                                        <div class="fw-bold fs-5 text-dark">{{ $s['total_tidak_hadir'] }} Kali</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 bg-white rounded border border-danger-subtle bg-danger-subtle">
                                        <div class="small text-danger fw-semibold">Alfa di Jurnal</div>
                                        <div class="fw-bold fs-5 text-danger">{{ $s['alfa_count'] }} Kali</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 bg-white rounded border">
                                        <div class="small text-muted">Izin (Surat)</div>
                                        <div class="fw-bold fs-5 text-primary">{{ $s['izin_count'] }} Kali</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 bg-white rounded border">
                                        <div class="small text-muted">Sakit</div>
                                        <div class="fw-bold fs-5 text-info">{{ $s['sakit_count'] }} Kali</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tabel Riwayat Lengkap Setiap Sesi Ketidakhadiran --}}
                        <div class="p-3">
                            <h6 class="fw-bold text-dark mb-2">
                                <i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i>
                                Log Tanggal, Sumber Absensi, dan Alasan Tidak Berangkat:
                            </h6>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 40px;">No</th>
                                            <th style="width: 130px;">Tanggal KBM / Izin</th>
                                            <th class="text-center" style="width: 110px;">Sumber & Status</th>
                                            <th>Alasan & Catatan Ketidakhadiran</th>
                                            <th style="width: 150px;">Pencatat / Verifikasi</th>
                                            <th class="text-center" style="width: 70px;">Surat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($s['riwayat'] as $idx => $r)
                                            <tr class="{{ $r['status'] === 'Alfa' ? 'table-danger' : '' }}">
                                                <td class="text-center text-muted fw-semibold">{{ $idx + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold text-dark">
                                                        {{ $r['start_date'] ? \Carbon\Carbon::parse($r['start_date'])->translatedFormat('d M Y') : '-' }}
                                                    </div>
                                                    @if(!empty($r['end_date']) && $r['start_date'] !== $r['end_date'])
                                                        <small class="text-muted">
                                                            s/d {{ \Carbon\Carbon::parse($r['end_date'])->translatedFormat('d M Y') }}
                                                        </small>
                                                    @else
                                                        <small class="text-muted">
                                                            {{ $r['start_date'] ? \Carbon\Carbon::parse($r['start_date'])->translatedFormat('l') : '-' }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($r['status'] === 'Alfa')
                                                        <span class="badge bg-danger text-white d-block mb-1">Alfa</span>
                                                        <small class="text-muted" style="font-size: 0.68rem;">Jurnal Guru</small>
                                                    @elseif($r['status_approval'] === 'Approved')
                                                        <span class="badge bg-success text-white d-block mb-1">Disetujui</span>
                                                        <small class="text-muted" style="font-size: 0.68rem;">Surat Izin</small>
                                                    @elseif($r['status_approval'] === 'Pending')
                                                        <span class="badge bg-warning text-dark d-block mb-1">Pending</span>
                                                        <small class="text-muted" style="font-size: 0.68rem;">Surat Izin</small>
                                                    @else
                                                        <span class="badge bg-danger text-white d-block mb-1">Ditolak</span>
                                                        <small class="text-muted" style="font-size: 0.68rem;">Surat Izin</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="p-2 rounded {{ $r['status'] === 'Alfa' ? 'bg-danger-subtle border-start border-3 border-danger' : 'bg-light border-start border-3 border-primary' }}">
                                                        <span class="text-dark">{{ $r['alasan'] }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="small fw-semibold {{ $r['status'] === 'Alfa' ? 'text-danger' : 'text-success' }}">
                                                        {{ $r['verified_by'] ?? '-' }}
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if(!empty($r['document_path']))
                                                        <a href="{{ asset($r['document_path']) }}" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.72rem;" title="Lihat Dokumen Surat">
                                                            <i class="fa-solid fa-file-arrow-down"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-3 text-muted">Belum ada riwayat ketidakhadiran tercatat.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Modal --}}
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endforeach

@endsection
