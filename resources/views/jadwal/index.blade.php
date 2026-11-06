@extends('layouts.app')

@section('title', 'Manajemen Jadwal Pelajaran - UKK SCP')

@section('page-header')
@php
    $role = session('id_role');
    $backUrl = ($role == 4) ? route('waka.dashboard') : route('guru.dashboard');
@endphp
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Manajemen Jadwal Pelajaran</h1>
        <p class="page-subtitle">Penyusunan jadwal kegiatan belajar mengajar dengan sistem verifikasi anti-tabrakan</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if(isset($config) && $config->is_schedule_locked)
            <span class="badge-soft badge-soft-danger px-3 py-2">
                <i class="fa-solid fa-lock"></i> Jadwal Terkunci
            </span>
        @else
            <span class="badge-soft badge-soft-success px-3 py-2">
                <i class="fa-solid fa-lock-open"></i> Akses Input Aktif
            </span>
        @endif
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection

@section('content')
<!-- Peringatan jika jadwal terkunci -->
@if(isset($config) && $config->is_schedule_locked)
    <div class="app-alert app-alert-warning mb-4">
        <i class="fa-solid fa-circle-exclamation fs-5 text-warning mt-1"></i>
        <div>
            <strong>Perhatian: Jadwal Pelajaran Dikunci oleh Waka Kurikulum.</strong><br>
            Sistem saat ini berada dalam mode finalisasi jadwal. Penambahan jadwal baru dinonaktifkan sementara waktu.
        </div>
    </div>
@endif

<div class="row g-4">
    <!-- Kolom Kiri: Form Input Jadwal & Pengecekan Anti-Tabrakan -->
    <div class="col-lg-5">
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title">
                    <i class="fa-regular fa-square-plus text-primary"></i>
                    Form Input & Uji Anti-Tabrakan
                </h5>
            </div>
            <div class="app-card-body">
                <form action="{{ route('jadwal.store') }}" method="POST">
                    @csrf
                    
                    <!-- Tahun Ajaran -->
                    <div class="mb-3">
                        <label class="form-label" for="id_tahun_ajaran">Tahun Ajaran / Semester <span class="text-danger">*</span></label>
                        <select id="id_tahun_ajaran" name="id_tahun_ajaran" class="form-select" required {{ (isset($config) && $config->is_schedule_locked) ? 'disabled' : '' }}>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            @foreach($tahunAjaran as $ta)
                                <option value="{{ $ta->id_tahun_ajaran }}" {{ old('id_tahun_ajaran') == $ta->id_tahun_ajaran ? 'selected' : '' }}>
                                    {{ $ta->nama_semester ?? ($ta->id_tahun_ajaran ? 'Semester '.$ta->id_tahun_ajaran : '-') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kelas -->
                    <div class="mb-3">
                        <label class="form-label" for="id_kelas">Target Kelas <span class="text-danger">*</span></label>
                        <select id="id_kelas" name="id_kelas" class="form-select" required {{ (isset($config) && $config->is_schedule_locked) ? 'disabled' : '' }}>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($kelas as $k)
                                <option value="{{ $k->id_kelas }}" {{ old('id_kelas') == $k->id_kelas ? 'selected' : '' }}>
                                    {{ $k->tingkat ?? '' }} {{ $k->nama_jurusan ?? $k->id_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mata Pelajaran -->
                    <div class="mb-3">
                        <label class="form-label" for="id_mapel">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select id="id_mapel" name="id_mapel" class="form-select" required {{ (isset($config) && $config->is_schedule_locked) ? 'disabled' : '' }}>
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            @foreach($mapels as $m)
                                <option value="{{ $m->id_mapel }}" {{ old('id_mapel') == $m->id_mapel ? 'selected' : '' }}>
                                    {{ $m->nama_mapel ?? ($m->id_mapel ? 'Mapel '.$m->id_mapel : '-') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Guru Pengampu -->
                    <div class="mb-3">
                        <label class="form-label">Guru Pengampu <span class="text-danger">*</span></label>
                        @if(isset($guruLogin) && $guruLogin)
                            <input type="hidden" name="id_guru" value="{{ $guruLogin->id_guru }}">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-chalkboard-user"></i></span>
                                <input type="text" class="form-control bg-light fw-bold" value="{{ $guruLogin?->nama_guru ?? '-' }}" readonly>
                            </div>
                            <div class="form-text">Terisi otomatis sesuai akun login guru Anda.</div>
                        @else
                            <select name="id_guru" class="form-select" required {{ (isset($config) && $config->is_schedule_locked) ? 'disabled' : '' }}>
                                <option value="">-- Pilih Guru Pengampu --</option>
                                @foreach($gurus as $g)
                                    <option value="{{ $g->id_guru }}" {{ old('id_guru') == $g->id_guru ? 'selected' : '' }}>
                                        {{ $g->nama_guru ?? ($g->id_guru ? 'Guru '.$g->id_guru : '-') }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- Ruangan Default -->
                    <div class="mb-3">
                        <label class="form-label" for="id_ruangan_default">Ruangan Belajar <span class="text-danger">*</span></label>
                        <select id="id_ruangan_default" name="id_ruangan_default" class="form-select" required {{ (isset($config) && $config->is_schedule_locked) ? 'disabled' : '' }}>
                            <option value="">-- Pilih Ruangan Kelas / Lab --</option>
                            @foreach($ruangans as $r)
                                <option value="{{ $r->id_ruangan }}" {{ old('id_ruangan_default') == $r->id_ruangan ? 'selected' : '' }}>
                                    {{ $r->nama_ruangan ?? ($r->id_ruangan ? 'Ruang '.$r->id_ruangan : '-') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Hari -->
                    <div class="mb-3">
                        <label class="form-label" for="hari">Hari Pembelajaran <span class="text-danger">*</span></label>
                        <select id="hari" name="hari" class="form-select" required {{ (isset($config) && $config->is_schedule_locked) ? 'disabled' : '' }}>
                            <option value="">-- Pilih Hari --</option>
                            <option value="Senin" {{ old('hari') == 'Senin' ? 'selected' : '' }}>Senin</option>
                            <option value="Selasa" {{ old('hari') == 'Selasa' ? 'selected' : '' }}>Selasa</option>
                            <option value="Rabu" {{ old('hari') == 'Rabu' ? 'selected' : '' }}>Rabu</option>
                            <option value="Kamis" {{ old('hari') == 'Kamis' ? 'selected' : '' }}>Kamis</option>
                            <option value="Jumat" {{ old('hari') == 'Jumat' ? 'selected' : '' }}>Jumat</option>
                        </select>
                    </div>

                    <!-- Sesi Jam Pelajaran (Berdasarkan Struktur Jam Sekolah) -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0 fw-bold">Sesi Jam Pelajaran <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.8rem;" data-bs-toggle="modal" data-bs-target="#modalStrukturSesi">
                                <i class="fa-solid fa-circle-info me-1"></i> Jadwal Jam & Istirahat
                            </button>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1" for="sesi_mulai">Sesi Mulai (Jam Ke-)</label>
                                <select id="sesi_mulai" name="sesi_mulai" class="form-select" required {{ (isset($config) && $config->is_schedule_locked) ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Mulai --</option>
                                    @if(isset($daftarSesi))
                                        @foreach($daftarSesi as $num => $sesi)
                                            <option value="{{ $num }}" data-mulai="{{ $sesi['mulai'] }}" data-selesai="{{ $sesi['selesai'] }}" {{ old('sesi_mulai') == $num ? 'selected' : '' }}>
                                                Jam {{ $num }} ({{ $sesi['mulai'] }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1" for="sesi_selesai">Sesi Selesai (Jam Ke-)</label>
                                <select id="sesi_selesai" name="sesi_selesai" class="form-select" required {{ (isset($config) && $config->is_schedule_locked) ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Selesai --</option>
                                    @if(isset($daftarSesi))
                                        @foreach($daftarSesi as $num => $sesi)
                                            <option value="{{ $num }}" data-mulai="{{ $sesi['mulai'] }}" data-selesai="{{ $sesi['selesai'] }}" {{ old('sesi_selesai') == $num ? 'selected' : '' }}>
                                                Jam {{ $num }} ({{ $sesi['selesai'] }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <!-- Hidden input jam_mulai & jam_selesai agar tetap kompatibel penuh -->
                        <input type="hidden" id="jam_mulai" name="jam_mulai" value="{{ old('jam_mulai') }}">
                        <input type="hidden" id="jam_selesai" name="jam_selesai" value="{{ old('jam_selesai') }}">

                        <!-- Preview Waktu Sesi -->
                        <div id="previewWaktuSesi" class="mt-2 p-2 bg-light border rounded small d-none">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-primary fw-bold" id="textWaktuRange">
                                    <i class="fa-regular fa-clock me-1"></i> --:-- - --:--
                                </span>
                                <span class="badge bg-primary text-white" id="textJumlahJam">0 Jam Pelajaran</span>
                            </div>
                            <div id="textPeringatanIstirahat" class="text-muted small mt-1 d-none" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-mug-hot me-1 text-warning"></i> <span></span>
                            </div>
                        </div>
                    </div>

                    @if(isset($config) && $config->is_schedule_locked)
                        <button type="button" class="btn btn-secondary w-100" disabled>
                            <i class="fa-solid fa-lock"></i> Jadwal Sedang Dikunci Waka
                        </button>
                    @else
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-shield-halved"></i> Simpan & Uji Anti-Tabrakan
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Daftar Jadwal Aktif (dikelompokkan per kelas) -->
    <div class="col-lg-7">
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title">
                    <i class="fa-solid fa-table-list text-primary"></i>
                    Daftar Jadwal Pelajaran Aktif
                </h5>
                <span class="badge-soft badge-soft-secondary">
                    {{ $paginator->total() }} Kelas &middot; {{ $totalJadwal }} Jadwal
                </span>
            </div>

            {{-- Filter & pencarian --}}
            <div class="app-card-body border-bottom pb-3">
                <form method="GET" action="{{ route('jadwal.index') }}" class="row g-2 align-items-end">
                    <div class="col-12">
                        <label class="form-label small text-muted mb-1" for="filter_q">Cari</label>
                        <input type="search" id="filter_q" name="q" value="{{ $filter['q'] }}" class="form-control"
                               placeholder="Kelas, mapel, guru, ruangan, atau hari...">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1" for="filter_putaran">Putaran</label>
                        <select id="filter_putaran" name="putaran" class="form-select form-select-sm">
                            <option value="" {{ $filter['putaran'] === '' ? 'selected' : '' }}>Semua</option>
                            <option value="Putaran 1" {{ $filter['putaran'] === 'Putaran 1' ? 'selected' : '' }}>Putaran 1{{ $cycleAktif === 'Putaran 1' ? ' (aktif)' : '' }}</option>
                            <option value="Putaran 2" {{ $filter['putaran'] === 'Putaran 2' ? 'selected' : '' }}>Putaran 2{{ $cycleAktif === 'Putaran 2' ? ' (aktif)' : '' }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-1" for="filter_hari">Hari</label>
                        <select id="filter_hari" name="hari" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach(['Senin','Selasa','Rabu','Kamis','Jumat'] as $h)
                                <option value="{{ $h }}" {{ $filter['hari'] === $h ? 'selected' : '' }}>{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small text-muted mb-1" for="filter_kelas">Kelas</label>
                        <select id="filter_kelas" name="kelas" class="form-select form-select-sm">
                            <option value="">Semua kelas</option>
                            @foreach($kelas as $k)
                                <option value="{{ $k->id_kelas }}" {{ (string) $filter['kelas'] === (string) $k->id_kelas ? 'selected' : '' }}>
                                    {{ trim(($k->tingkat ?? '') . ' ' . ($k->nama_jurusan ?? $k->id_kelas)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-magnifying-glass"></i> Cari
                        </button>
                        <a href="{{ route('jadwal.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="app-card-body p-0">
                @php
                    // Buka semua grup saat sedang mencari/memfilter; jika tidak, hanya grup pertama
                    $sedangFilter = $filter['q'] !== '' || $filter['hari'] !== '' || $filter['kelas'] !== '';
                @endphp

                @if($jadwalGroups->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fa-regular fa-calendar-xmark fa-2x mb-2 text-secondary d-block"></i>
                        Tidak ada jadwal yang cocok dengan pencarian/filter.
                    </div>
                @else
                    <div class="accordion accordion-flush" id="accordionJadwalKelas">
                        @foreach($jadwalGroups as $idKelas => $rows)
                            @php
                                $k0 = $rows->first()->kelas;
                                $namaKelas = $k0
                                    ? (trim(($k0->tingkat ?? '') . ' ' . ($k0->jurusan?->nama_jurusan ?? '')) ?: 'Kelas ' . $idKelas)
                                    : 'Kelas ' . $idKelas;
                                $terbuka = $sedangFilter || $loop->first;
                                $jumlahMapel = $rows->pluck('id_mapel')->unique()->count();
                                $perHari = $rows->groupBy('hari');
                            @endphp
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="hd-{{ $idKelas }}">
                                    <button class="accordion-button {{ $terbuka ? '' : 'collapsed' }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#cl-{{ $idKelas }}"
                                            aria-expanded="{{ $terbuka ? 'true' : 'false' }}" aria-controls="cl-{{ $idKelas }}">
                                        <div class="d-flex flex-column flex-md-row w-100 justify-content-between align-items-md-center gap-1 me-2">
                                            <span class="fw-bold text-primary">{{ $namaKelas }}</span>
                                            <span class="small text-muted">
                                                {{ $rows->count() }} jadwal &middot; {{ $jumlahMapel }} mapel &middot; {{ $perHari->count() }} hari
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="cl-{{ $idKelas }}" class="accordion-collapse collapse {{ $terbuka ? 'show' : '' }}" aria-labelledby="hd-{{ $idKelas }}">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive">
                                            <table class="table app-table align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Hari & Waktu</th>
                                                        <th>Mapel</th>
                                                        <th>Guru & Ruangan</th>
                                                        <th class="text-center">Siklus</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($rows as $j)
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                                    <span class="fw-bold text-dark">{{ $j->hari ?? '-' }}</span>
                                                                    @if($j->label_sesi)
                                                                        <span class="badge bg-primary text-white" style="font-size: 0.68rem;">{{ $j->label_sesi }}</span>
                                                                    @endif
                                                                </div>
                                                                <small class="text-muted">
                                                                    <i class="fa-regular fa-clock me-1"></i>
                                                                    {{ $j->jam_mulai ? \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') : '--:--' }} - {{ $j->jam_selesai ? \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') : '--:--' }}
                                                                </small>
                                                            </td>
                                                            <td class="fw-semibold">{{ $j->mataPelajaran?->nama_mapel ?? ($j->id_mapel ? 'Mapel '.$j->id_mapel : 'Mapel Tidak Ditemukan') }}</td>
                                                            <td>
                                                                <div class="fw-semibold text-dark">{{ $j->guru?->nama_guru ?? ($j->id_guru ? 'Guru '.$j->id_guru : 'Guru Tidak Ditemukan') }}</div>
                                                                <small class="text-muted">
                                                                    <i class="fa-solid fa-door-open me-1"></i>
                                                                    {{ $j->ruangan?->nama_ruangan ?? ($j->id_ruangan_default ? 'Ruang '.$j->id_ruangan_default : 'Ruangan Tidak Ditemukan') }}
                                                                </small>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge-soft badge-soft-info">{{ $j->cycle_type ?? '-' }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($paginator->hasPages())
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 p-3 border-top">
                            <small class="text-muted">
                                Kelas {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
                            </small>
                            {{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal Struktur Jam Pelajaran & Istirahat --}}
<div class="modal fade" id="modalStrukturSesi" tabindex="-1" aria-labelledby="modalStrukturSesiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: var(--radius); overflow: hidden;">
            <div class="modal-header py-3 px-4 text-white" style="background: var(--color-dark, #0f2a5c);">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white p-2 rounded-3">
                        <i class="fa-solid fa-clock"></i>
                    </span>
                    <div>
                        <h6 class="modal-title mb-0 fw-bold" id="modalStrukturSesiLabel">Struktur Jam Pelajaran & Istirahat</h6>
                        <small class="text-white-50">Pedoman pembagian sesi jam pembelajaran sekolah</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 text-center align-middle" style="font-size: 0.88rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">Jam Ke-</th>
                                <th style="width: 45%;">Waktu</th>
                                <th style="width: 30%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-light text-muted">
                                <td>-</td>
                                <td>07.00 - 07.15</td>
                                <td><span class="badge bg-secondary">tadarus</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">1</td>
                                <td>07.15 - 07.55</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">2</td>
                                <td>07.55 - 08.35</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">3</td>
                                <td>08.35 - 09.15</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr class="table-warning bg-opacity-25">
                                <td class="fw-bold text-warning-emphasis">IST 1</td>
                                <td class="fw-semibold">09.15 - 09.30</td>
                                <td><span class="badge bg-warning text-dark">Istirahat Pagi (15 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">4</td>
                                <td>09.30 - 10.10</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">5</td>
                                <td>10.10 - 10.50</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">6</td>
                                <td>10.50 - 11.30</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr class="table-warning bg-opacity-25">
                                <td class="fw-bold text-warning-emphasis">IST 2</td>
                                <td class="fw-semibold">11.30 - 12.30</td>
                                <td><span class="badge bg-warning text-dark">Ishoma (60 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">7</td>
                                <td>12.30 - 13.10</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">8</td>
                                <td>13.10 - 13.50</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">9</td>
                                <td>13.50 - 14.30</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-primary">10</td>
                                <td>14.30 - 15.10</td>
                                <td><span class="badge bg-light text-dark border">KBM (40 Menit)</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sesiMulai = document.getElementById('sesi_mulai');
    const sesiSelesai = document.getElementById('sesi_selesai');
    const jamMulaiInput = document.getElementById('jam_mulai');
    const jamSelesaiInput = document.getElementById('jam_selesai');
    const previewBox = document.getElementById('previewWaktuSesi');
    const textRange = document.getElementById('textWaktuRange');
    const textJumlah = document.getElementById('textJumlahJam');
    const textIstirahat = document.getElementById('textPeringatanIstirahat');

    if (sesiMulai && sesiSelesai) {
        function updateSesiWaktu() {
            const valMulai = parseInt(sesiMulai.value);
            const valSelesai = parseInt(sesiSelesai.value);

            // Perbarui ketersediaan opsi sesi selesai: tidak boleh < sesi mulai
            Array.from(sesiSelesai.options).forEach(function(opt) {
                if (!opt.value) return;
                const optVal = parseInt(opt.value);
                if (valMulai && optVal < valMulai) {
                    opt.disabled = true;
                } else {
                    opt.disabled = false;
                }
            });

            // Jika sesi selesai lebih kecil dari sesi mulai, samakan dengan sesi mulai
            if (valMulai && (!valSelesai || valSelesai < valMulai)) {
                sesiSelesai.value = valMulai;
            }

            const optMulai = sesiMulai.options[sesiMulai.selectedIndex];
            const optSelesai = sesiSelesai.options[sesiSelesai.selectedIndex];

            if (optMulai && optMulai.dataset.mulai && optSelesai && optSelesai.dataset.selesai) {
                const jamMulaiVal = optMulai.dataset.mulai + ':00';
                const jamSelesaiVal = optSelesai.dataset.selesai + ':00';

                if (jamMulaiInput) jamMulaiInput.value = jamMulaiVal;
                if (jamSelesaiInput) jamSelesaiInput.value = jamSelesaiVal;

                if (previewBox) {
                    previewBox.classList.remove('d-none');
                    const jmlJam = (parseInt(sesiSelesai.value) - parseInt(sesiMulai.value) + 1);
                    textRange.innerHTML = '<i class="fa-regular fa-clock me-1"></i> ' + optMulai.dataset.mulai + ' - ' + optSelesai.dataset.selesai;
                    textJumlah.textContent = jmlJam + ' Jam Pelajaran';

                    // Cek jika rentang sesi mencakup waktu istirahat
                    const m = parseInt(sesiMulai.value);
                    const s = parseInt(sesiSelesai.value);
                    let infoIstirahat = [];
                    if (m <= 3 && s >= 4) {
                        infoIstirahat.push('Mencakup Istirahat 1 (09.15 - 09.30)');
                    }
                    if (m <= 6 && s >= 7) {
                        infoIstirahat.push('Mencakup Ishoma (11.30 - 12.30)');
                    }

                    if (infoIstirahat.length > 0 && textIstirahat) {
                        textIstirahat.classList.remove('d-none');
                        textIstirahat.querySelector('span').textContent = infoIstirahat.join(' & ');
                    } else if (textIstirahat) {
                        textIstirahat.classList.add('d-none');
                    }
                }
            } else if (previewBox) {
                previewBox.classList.add('d-none');
            }
        }

        sesiMulai.addEventListener('change', updateSesiWaktu);
        sesiSelesai.addEventListener('change', updateSesiWaktu);

        // Inisialisasi awal saat halaman dibuka
        if (sesiMulai.value) {
            updateSesiWaktu();
        }
    }
});
</script>
@endpush
