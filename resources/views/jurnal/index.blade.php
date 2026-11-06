@extends('layouts.app')

@section('title', 'Modul Jurnal Mengajar & Presensi Siswa - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Modul Jurnal Mengajar & Presensi</h1>
        <p class="page-subtitle">Pencatatan materi kegiatan belajar mengajar (KBM), dokumentasi foto, dan pencatatan presensi ketidakhadiran siswa per sesi kelas</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-primary border px-3 py-2 fw-semibold">
            <i class="fa-solid fa-calendar-day me-1"></i> {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
        </span>
        <a href="{{ route('guru.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>
</div>
@endsection

@section('content')
{{-- FORM UTAMA: Menggabungkan Pengisian Jurnal (Kiri) & Presensi Siswa (Kanan) --}}
<form action="{{ route('jurnal.store') }}" method="POST" enctype="multipart/form-data" id="formJurnal">
    @csrf

    @php
        $overdueIds = isset($overdueJadwals) ? $overdueJadwals->pluck('id_jadwal')->toArray() : [];
        $defaultSchedule = count($overdueIds) > 0 ? $overdueIds[0] : ($jadwals->first()?->id_jadwal ?? '');
        $selectedSchedule = old('schedule_id', request('schedule_id', $defaultSchedule));
    @endphp

    <div class="row g-4 mb-4">
        {{-- ========================================================== --}}
        {{-- KOLOM KIRI: Form Pengisian Jurnal & Foto KBM (Ditata Rapi) --}}
        {{-- ========================================================== --}}
        <div class="col-lg-6 col-xl-7">
            <div class="app-card h-100 shadow-sm border">
                <div class="app-card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white p-2 rounded-3">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </span>
                        <div>
                            <h5 class="app-card-title mb-0 fw-bold text-dark">Form Pengisian Jurnal Mengajar</h5>
                            <small class="text-muted">Isi agenda pembelajaran dan upload bukti foto kegiatan</small>
                        </div>
                    </div>
                    @if(isset($guru))
                        <span class="badge bg-light text-dark border">
                            <i class="fa-solid fa-chalkboard-user text-primary me-1"></i> {{ $guru->nama_guru ?? 'Guru' }}
                        </span>
                    @endif
                </div>

                <div class="app-card-body p-4">
                    {{-- 1. PILIH SESI JADWAL KELAS --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between" for="schedule_id">
                            <span>
                                <span class="badge bg-primary me-1" style="font-size: 0.7rem;">1</span>
                                Sesi Jadwal Mengajar <span class="text-danger">*</span>
                            </span>
                            @if(count($overdueIds) > 0)
                                <span class="badge bg-danger text-white" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Ada sesi terlambat diisi
                                </span>
                            @endif
                        </label>
                        
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-calendar-check"></i></span>
                            <select id="schedule_id" name="schedule_id" class="form-select {{ in_array($selectedSchedule, $overdueIds) ? 'border-danger' : '' }}" required>
                                <option value="">-- Pilih Sesi Jadwal Mengajar Anda --</option>
                                @foreach($jadwals as $j)
                                    @php
                                        $isOverdue = in_array($j->id_jadwal, $overdueIds);
                                        $isExpired = $j->is_expired ?? false;
                                        $expiredReason = $j->expired_reason ?? '';
                                        $deadlineFormatted = $j->deadline_formatted ?? '';
                                        $jamSelesaiFmt = $j->jam_selesai_formatted ?? ($j->jam_selesai ? \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') : '--:--');
                                        $kelasNama = $j->kelas?->nama_kelas ?? ($j->id_kelas ? 'Kelas '.$j->id_kelas : 'Kelas Tidak Ditentukan');
                                        $mapelNama = $j->mataPelajaran?->nama_mapel ?? ($j->id_mapel ? 'Mapel '.$j->id_mapel : 'Mapel Tidak Ditentukan');
                                        $jamMulai = $j->jam_mulai ? \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') : '--:--';
                                        $jamSelesai = $j->jam_selesai ? \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') : '--:--';
                                        $ruanganNama = $j->ruangan?->nama_ruangan ?? 'Ruang Tidak Ditentukan';
                                        $sesiLabel = $j->label_sesi;
                                        $displayWaktu = $sesiLabel ? $sesiLabel . ' (' . $jamMulai . ' - ' . $jamSelesai . ')' : $jamMulai . ' - ' . $jamSelesai;
                                    @endphp
                                    <option value="{{ $j->id_jadwal }}" 
                                            data-kelas-id="{{ $j->id_kelas }}"
                                            data-kelas="{{ $kelasNama }}"
                                            data-mapel="{{ $mapelNama }}"
                                            data-hari="{{ $j->hari ?? '-' }}"
                                            data-jam="{{ $displayWaktu }}"
                                            data-ruangan="{{ $ruanganNama }}"
                                            data-cycle="{{ $j->cycle_type ?? 'Semua Putaran' }}"
                                            data-expired="{{ $isExpired ? '1' : '0' }}"
                                            data-expired-reason="{{ $expiredReason }}"
                                            data-deadline="{{ $deadlineFormatted }}"
                                            data-jam-selesai="{{ $jamSelesaiFmt }}"
                                            {{ $selectedSchedule == $j->id_jadwal ? 'selected' : '' }} 
                                            class="{{ $isExpired ? 'text-danger' : ($isOverdue ? 'text-warning fw-bold' : '') }}"
                                            {{ $isExpired ? 'disabled' : '' }}>
                                        @if($isExpired) 
                                            [TERKUNCI: >5 JAM SELESAI KBM] 
                                        @elseif($isOverdue) 
                                            [TERLAMBAT >30 MENIT DIMULAI] 
                                        @endif
                                        [{{ $j->hari ?? '-' }} | {{ $displayWaktu }}] {{ $kelasNama }} — {{ $mapelNama }}
                                        @if(!$isExpired && $deadlineFormatted && $deadlineFormatted !== '-')
                                            (Batas: {{ $deadlineFormatted }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text small text-muted mt-1">
                            Pilih sesi jadwal mengajar Anda. Maksimal pengisian jurnal adalah <strong>5 jam setelah jam pembelajaran selesai</strong>.
                        </div>

                        {{-- Kartu Info Detail Jadwal Terpilih --}}
                        <div id="jadwalDetailBox" class="mt-3 p-3 rounded-3" style="{{ isset($activeJadwal) && $activeJadwal ? 'display: block;' : 'display: none;' }} background: #f0f7ff; border: 1px solid #cce4fc;">
                            <div class="row g-2 align-items-center">
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-white p-2 text-primary shadow-xs">
                                            <i class="fa-solid fa-graduation-cap"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block" style="font-size: 0.72rem;">Kelas & Mapel:</small>
                                            <span class="fw-bold text-dark small" id="boxKelasMapel">
                                                {{ $activeJadwal?->kelas?->nama_kelas ?? ($activeJadwal?->id_kelas ? 'Kelas '.$activeJadwal->id_kelas : '-') }} — {{ $activeJadwal?->mataPelajaran?->nama_mapel ?? ($activeJadwal?->id_mapel ? 'Mapel '.$activeJadwal->id_mapel : '-') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-white p-2 text-success shadow-xs">
                                            <i class="fa-solid fa-clock"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block" style="font-size: 0.72rem;">Hari & Jam Pelajaran:</small>
                                            <span class="fw-semibold text-dark small" id="boxHariJam">
                                                {{ $activeJadwal?->hari ?? '-' }}, {{ $activeJadwal?->jam_mulai ? \Carbon\Carbon::parse($activeJadwal->jam_mulai)->format('H:i') : '--:--' }} - {{ $activeJadwal?->jam_selesai ? \Carbon\Carbon::parse($activeJadwal->jam_selesai)->format('H:i') : '--:--' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-white text-secondary border small" id="boxRuangan">
                                            <i class="fa-solid fa-door-open me-1"></i> Ruangan: {{ $activeJadwal?->ruangan?->nama_ruangan ?? '-' }}
                                        </span>
                                        <span class="badge bg-white text-info border small" id="boxCycle">
                                            <i class="fa-solid fa-arrows-rotate me-1"></i> Putaran: {{ $activeJadwal?->cycle_type ?? 'Semua Putaran' }}
                                        </span>
                                    </div>
                                    <span class="badge bg-primary text-white small" id="badgeActiveDeadline">
                                        <i class="fa-solid fa-stopwatch me-1"></i> Batas 5 Jam: <strong id="textDeadlineVal">{{ $activeJadwal?->deadline_formatted ?? '-' }}</strong>
                                    </span>
                                </div>

                                {{-- Peringatan jika jadwal terkunci karena lewat batas 5 jam --}}
                                <div class="col-12 mt-2 {{ ($activeJadwal?->is_expired ?? false) ? '' : 'd-none' }}" id="alertScheduleExpired">
                                    <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2 border-0 shadow-xs">
                                        <i class="fa-solid fa-ban fs-5 text-danger"></i>
                                        <div>
                                            <strong>Sesi Terkunci (> 5 Jam Selesai KBM):</strong>
                                            <span id="textExpiredReason">{{ $activeJadwal?->expired_reason ?? 'Batas waktu pengisian jurnal maksimal 5 jam setelah jam pembelajaran selesai.' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. TOPIK / RANGKUMAN MATERI PEMBELAJARAN --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark" for="topic_description">
                            <span class="badge bg-primary me-1" style="font-size: 0.7rem;">2</span>
                            Topik / Rangkuman Materi Pembelajaran (KBM) <span class="text-danger">*</span>
                        </label>
                        <textarea id="topic_description" 
                                  name="topic_description" 
                                  class="form-control" 
                                  rows="4" 
                                  required 
                                  placeholder="Contoh: Mempelajari konsep relasi database, pembuatan migration tabel presensi, serta sesi tanya jawab dan latihan studi kasus...">{{ old('topic_description') }}</textarea>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <div class="form-text small text-muted">
                                Tuliskan agenda pokok, materi, capaian atau kendala selama kegiatan belajar berlangsung.
                            </div>
                            <small class="text-muted font-monospace" id="charCount">0 karakter</small>
                        </div>
                    </div>

                    {{-- 3. AMBIL FOTO BUKTI DOKUMENTASI DARI KAMERA --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark" for="photo">
                            <span class="badge bg-primary me-1" style="font-size: 0.7rem;">3</span>
                            <i class="fa-solid fa-camera text-primary me-1"></i> Foto Bukti Dokumentasi KBM (Wajib Kamera Langsung) <span class="text-danger">*</span>
                        </label>
                        
                        <div class="photo-upload-container p-3 rounded-3 border border-2 border-dashed text-center position-relative" id="dropArea" style="background: #f8fafc; border-color: #cbd5e1 !important;">
                            {{-- Input tersembunyi yang diisi oleh jepretan kamera langsung --}}
                            <input type="hidden" id="photo_base64" name="photo_base64">
                            
                            {{-- Input kamera langsung bawaan perangkat (Android, iOS, Laptop/PC) --}}
                            <input type="file" id="directCameraCapture" accept="image/*" capture="environment" class="d-none">

                            {{-- State A: Belum Ambil Foto --}}
                            <div id="uploadPrompt" class="py-3">
                                <div class="rounded-circle d-inline-flex p-3 text-white mb-2 shadow-sm" style="background: linear-gradient(135deg, #0284c7, #0ea5e9);">
                                    <i class="fa-solid fa-camera fa-2x"></i>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.95rem;">Wajib Ambil Foto Langsung dari Kamera</h6>
                                <p class="text-muted small mb-3">Foto dokumentasi kegiatan belajar mengajar wajib diambil secara langsung dari kamera perangkat saat sesi KBM berlangsung.</p>
                                
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <button type="button" class="btn btn-primary px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2" onclick="triggerDeviceCamera()">
                                        <i class="fa-solid fa-camera fa-lg"></i>
                                        <span>Buka Kamera & Ambil Foto</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" onclick="openCameraModal()" title="Buka video stream live viewfinder">
                                        <i class="fa-solid fa-video me-1"></i>
                                        <span>Live Viewfinder Web</span>
                                    </button>
                                </div>
                                <div class="form-text small text-danger mt-2">
                                    <i class="fa-solid fa-shield-halved me-1"></i> Foto otomatis diberi watermark tanggal & waktu KBM. Berkas unggahan dari galeri dinonaktifkan.
                                </div>
                            </div>

                            {{-- State B: Preview Foto Hasil Kamera --}}
                            <div id="uploadPreview" class="d-none text-center py-2">
                                <div class="position-relative d-inline-block">
                                    <img id="previewImg" src="#" alt="Hasil Foto Kamera" class="img-thumbnail rounded-3 shadow-sm mb-2" style="max-height: 220px; object-fit: cover;">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle m-1 p-1 shadow-sm" style="width: 28px; height: 28px; line-height: 1;" onclick="clearPhotoUpload()" title="Hapus foto">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle mb-1">
                                        <i class="fa-solid fa-circle-check me-1"></i> Foto Kamera Berhasil Diambil
                                    </span>
                                    <span class="fw-semibold text-dark small d-block" id="previewFileName">foto_kbm.jpg</span>
                                    <div class="mt-2 d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-1 px-3" onclick="triggerDeviceCamera()">
                                            <i class="fa-solid fa-arrows-rotate me-1"></i> Ambil Ulang Foto
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" onclick="clearPhotoUpload()">
                                            <i class="fa-solid fa-trash me-1"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TOMBOL SIMPAN UTAMA --}}
                    <div class="pt-2 border-top">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" id="btnSubmitJurnal" style="font-size: 0.95rem;">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Jurnal & Presensi Siswa
                        </button>
                        <small class="text-muted d-block text-center mt-2" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Jurnal dan data presensi otomatis disinkronkan ke rekap Waka Kurikulum.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- KOLOM KANAN: Presensi Siswa di Jam Pelajaran (Disebelah Kanan) --}}
        {{-- ========================================================== --}}
        <div class="col-lg-6 col-xl-5">
            <div class="app-card h-100 shadow-sm border d-flex flex-column">
                <div class="app-card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark p-2 rounded-3">
                            <i class="fa-solid fa-clipboard-user"></i>
                        </span>
                        <div>
                            <h5 class="app-card-title mb-0 fw-bold text-dark">Presensi Ketidakhadiran Siswa</h5>
                            <small class="text-muted">Cek kehadiran siswa di jam pelajaran ini</small>
                        </div>
                    </div>
                    <span class="badge bg-secondary text-white" id="badgeJumlahSiswa" style="font-size: 0.75rem;">
                        {{ isset($siswaList) ? $siswaList->count() : 0 }} Siswa
                    </span>
                </div>

                <div class="app-card-body p-3 flex-grow-1 d-flex flex-column">
                    {{-- Petunjuk Pengisian --}}
                    <div class="alert alert-light border d-flex gap-2 align-items-start py-2 px-3 mb-3" style="font-size: 0.8rem; background: #fdfefe;">
                        <i class="fa-solid fa-circle-info text-primary mt-1"></i>
                        <div>
                            Secara default seluruh siswa dianggap <span class="badge bg-success" style="font-size: 0.68rem;">Hadir</span>. Cukup <strong>centang siswa yang tidak hadir</strong> lalu tentukan statusnya (Sakit / Izin / Alfa).
                        </div>
                    </div>

                    {{-- Bar Ringkasan Status Kehadiran (Live Counters) --}}
                    <div class="p-2 rounded-3 border mb-3 bg-light" id="statCountersBox">
                        <div class="row g-2 text-center">
                            <div class="col-3">
                                <div class="bg-white py-1 px-1 rounded border">
                                    <span class="d-block text-success fw-bold" id="countHadir" style="font-size: 1.05rem;">{{ isset($siswaList) ? $siswaList->count() : 0 }}</span>
                                    <small class="text-muted" style="font-size: 0.68rem;"><i class="fa-solid fa-check text-success"></i> Hadir</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-white py-1 px-1 rounded border">
                                    <span class="d-block text-info fw-bold" id="countSakit" style="font-size: 1.05rem;">0</span>
                                    <small class="text-muted" style="font-size: 0.68rem;"><i class="fa-solid fa-briefcase-medical text-info"></i> Sakit</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-white py-1 px-1 rounded border">
                                    <span class="d-block text-warning fw-bold" id="countIzin" style="font-size: 1.05rem;">0</span>
                                    <small class="text-muted" style="font-size: 0.68rem;"><i class="fa-solid fa-envelope text-warning"></i> Izin</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-white py-1 px-1 rounded border">
                                    <span class="d-block text-danger fw-bold" id="countAlfa" style="font-size: 1.05rem;">0</span>
                                    <small class="text-muted" style="font-size: 0.68rem;"><i class="fa-solid fa-xmark text-danger"></i> Alfa</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Filter & Search Siswa --}}
                    <div id="filterSiswaBox" class="mb-2" style="{{ isset($siswaList) && $siswaList->count() > 0 ? '' : 'display: none;' }}">
                        <div class="d-flex gap-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="searchSiswaInput" class="form-control" placeholder="Cari nama atau NISN siswa..." onkeyup="filterSiswaList()">
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" onclick="resetAllHadir()" title="Hapus semua centang dan set semua Hadir">
                                <i class="fa-solid fa-rotate-left"></i> Reset
                            </button>
                        </div>
                    </div>

                    {{-- Container Daftar Siswa (Langsung Render Siswa dari Database) --}}
                    <div class="daftar-siswa-wrapper flex-grow-1 position-relative" style="min-height: 280px;">
                        {{-- Loading Spinner (hanya saat berganti jadwal) --}}
                        <div id="absensiLoading" class="text-center py-3 d-none">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="small text-muted ms-2 fw-semibold">Memuat data siswa kelas...</span>
                        </div>

                        {{-- Wadah List Siswa --}}
                        <div id="daftarSiswaContainer">
                            @if(isset($siswaList) && $siswaList->count() > 0)
                                <div id="siswaList" class="siswa-presensi-list">
                                    @foreach($siswaList as $index => $siswa)
                                        @php
                                            $parts = explode(' ', trim($siswa->nama_siswa ?? ''));
                                            $initial = (isset($parts[0][0]) ? $parts[0][0] : '') . (isset($parts[1][0]) ? $parts[1][0] : '');
                                            $initial = strtoupper($initial ?: 'S');
                                        @endphp
                                        <div class="siswa-presensi-card" id="siswaRow_{{ $siswa->id_siswa }}" data-nama="{{ strtolower($siswa->nama_siswa) }}" data-nisn="{{ $siswa->nisn }}">
                                            <div class="d-flex align-items-center justify-content-between p-2">
                                                <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden" onclick="triggerCheckbox({{ $siswa->id_siswa }})" style="cursor: pointer;">
                                                    <div class="form-check m-0" onclick="event.stopPropagation()">
                                                        <input class="form-check-input absensi-checkbox" type="checkbox" 
                                                               id="chk_{{ $siswa->id_siswa }}" 
                                                               data-siswa-id="{{ $siswa->id_siswa }}"
                                                               {{ !empty($siswa->is_approved_leave) ? 'checked' : '' }}
                                                               onchange="toggleAbsensiRow(this, {{ $siswa->id_siswa }})">
                                                    </div>
                                                    <div class="siswa-avatar-initial">
                                                        {{ $initial }}
                                                    </div>
                                                    <div class="text-truncate">
                                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.85rem;">
                                                            {{ $siswa->nama_siswa }}
                                                        </div>
                                                        <small class="text-muted font-monospace" style="font-size: 0.72rem;">
                                                            NISN: {{ $siswa->nisn ?: '-' }}
                                                        </small>
                                                        @if(!empty($siswa->is_approved_leave))
                                                            <div class="mt-1">
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">
                                                                    <i class="fa-solid fa-circle-check me-1"></i> Disetujui Wali Kelas/BK: "{{ $siswa->leave_reason }}"
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div>
                                                    @if(!empty($siswa->is_approved_leave))
                                                        <span class="badge {{ $siswa->leave_status === 'Sakit' ? 'bg-info-soft text-info border border-info-subtle' : 'bg-warning-soft text-warning-emphasis border border-warning-subtle' }} absensi-status-badge" id="statusBadge_{{ $siswa->id_siswa }}" style="font-size: 0.7rem; padding: 0.35rem 0.6rem;">
                                                            <i class="fa-solid {{ $siswa->leave_status === 'Sakit' ? 'fa-briefcase-medical' : 'fa-envelope-open-text' }} me-1"></i> {{ $siswa->leave_status }} (Disetujui BK)
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success-soft text-success border border-success-subtle absensi-status-badge" id="statusBadge_{{ $siswa->id_siswa }}" style="font-size: 0.7rem; padding: 0.35rem 0.6rem;">
                                                            <i class="fa-solid fa-check me-1"></i> Hadir
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Expanded detail ketika dicentang Tidak Masuk --}}
                                            <div class="absensi-detail-box p-2 border-top bg-light" id="absensiDetail_{{ $siswa->id_siswa }}" style="display: {{ !empty($siswa->is_approved_leave) ? 'block' : 'none' }};">
                                                <input type="hidden" name="absensi[{{ $index }}][student_id]" value="{{ $siswa->id_siswa }}" {{ !empty($siswa->is_approved_leave) ? '' : 'disabled' }} id="inputSiswaId_{{ $siswa->id_siswa }}">
                                                <div class="row g-2">
                                                    <div class="col-sm-5">
                                                        <select class="form-select form-select-sm absensi-status-select fw-semibold" 
                                                                name="absensi[{{ $index }}][status]" 
                                                                {{ !empty($siswa->is_approved_leave) ? '' : 'disabled' }} 
                                                                id="selectStatus_{{ $siswa->id_siswa }}"
                                                                onchange="updateStatusBadge({{ $siswa->id_siswa }}, this.value)">
                                                            <option value="Sakit" {{ (!empty($siswa->is_approved_leave) && $siswa->leave_status === 'Sakit') ? 'selected' : '' }}>Sakit</option>
                                                            <option value="Izin" {{ (!empty($siswa->is_approved_leave) && $siswa->leave_status === 'Izin') ? 'selected' : '' }}>Izin</option>
                                                            <option value="Alfa" {{ empty($siswa->is_approved_leave) ? 'selected' : '' }}>Alfa</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-7">
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="absensi[{{ $index }}][keterangan]" 
                                                               placeholder="Alasan / ket (opsional)..."
                                                               value="{{ !empty($siswa->is_approved_leave) ? 'Izin disetujui Wali Kelas/BK: ' . $siswa->leave_reason : '' }}"
                                                               {{ !empty($siswa->is_approved_leave) ? '' : 'disabled' }} 
                                                               id="inputKet_{{ $siswa->id_siswa }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-users-slash fa-2x d-block mb-2 text-warning"></i>
                                    <h6 class="text-dark fw-bold mb-1">Belum Ada Data Siswa</h6>
                                    <p class="small text-muted mb-0">Tidak ada siswa aktif terdaftar pada kelas ini di database.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Footer Info --}}
                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size: 0.75rem;">
                        <span><i class="fa-solid fa-lightbulb text-warning me-1"></i> Tips: Klik nama/kotak untuk menandai</span>
                        <span id="labelKelasAktif" class="fw-semibold text-primary">
                            {{ $activeJadwal?->kelas?->nama_kelas ?? ($activeJadwal?->id_kelas ? 'Kelas '.$activeJadwal->id_kelas : '-') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- ========================================================== --}}
{{-- BAGIAN BAWAH: Riwayat Jurnal Mengajar Saya (Lebar Penuh) --}}
{{-- ========================================================== --}}
<div class="row">
    <div class="col-12">
        <div class="app-card shadow-sm border">
            <div class="app-card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary text-white p-2 rounded-3">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>
                    <div>
                        <h5 class="app-card-title mb-0 fw-bold text-dark">Riwayat Jurnal Mengajar Saya</h5>
                        <small class="text-muted">Daftar agenda kegiatan dan absensi yang pernah Anda simpan</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        Total Rekaman: <strong>{{ count($jurnalList) }} Jurnal</strong>
                    </span>
                </div>
            </div>
            
            <div class="app-card-body p-0">
                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th style="width: 140px;">Tanggal & Waktu</th>
                                <th style="width: 220px;">Jadwal & Kelas</th>
                                <th>Topik / Rangkuman Materi</th>
                                <th class="text-center" style="width: 140px;">Presensi Siswa</th>
                                <th class="text-center" style="width: 120px;">Bukti Foto</th>
                                <th class="text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jurnalList as $index => $item)
                                <tr>
                                    <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            {{ $item->date ? \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') : '-' }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $item->date ? \Carbon\Carbon::parse($item->date)->translatedFormat('l') : '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($item->jadwal)
                                            <div class="fw-bold text-primary">
                                                {{ $item->jadwal->kelas?->nama_kelas ?? ($item->jadwal->id_kelas ? 'Kelas '.$item->jadwal->id_kelas : 'Kelas Tidak Ditemukan') }}
                                            </div>
                                            <div class="small text-dark fw-semibold">
                                                {{ $item->jadwal->mataPelajaran?->nama_mapel ?? ($item->jadwal->id_mapel ? 'Mapel '.$item->jadwal->id_mapel : 'Mapel Tidak Ditemukan') }}
                                            </div>
                                            <div class="d-flex gap-1 mt-1">
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                    {{ $item->jadwal->hari ?? '-' }} | {{ $item->jadwal->label_sesi ? $item->jadwal->label_sesi . ' (' . ($item->jadwal->jam_mulai ? \Carbon\Carbon::parse($item->jadwal->jam_mulai)->format('H:i') : '--:--') . ')' : ($item->jadwal->jam_mulai ? \Carbon\Carbon::parse($item->jadwal->jam_mulai)->format('H:i') : '--:--') }}
                                                </span>
                                                <span class="badge bg-light text-info border" style="font-size: 0.68rem;">
                                                    {{ $item->jadwal->cycle_type ?? '-' }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-muted small">Jadwal telah dihapus</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-dark small" style="max-width: 380px; line-height: 1.5;">
                                            {{ $item->topic_description ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $absenTidakHadir = $item->attendances->where('status', '!=', 'Hadir');
                                        @endphp
                                        @if($absenTidakHadir->count() > 0)
                                            <button type="button" class="btn btn-outline-warning btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalAbsensi{{ $item->id }}" title="Lihat detail siswa tidak hadir">
                                                <i class="fa-solid fa-user-xmark me-1"></i>
                                                <span>{{ $absenTidakHadir->count() }} Absen</span>
                                            </button>
                                        @else
                                            <span class="badge bg-success py-1 px-2" style="font-size: 0.75rem;" title="Semua siswa hadir lengkap">
                                                <i class="fa-solid fa-check me-1"></i> Semua Hadir
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item->photo_path)
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm py-1 px-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalFotoBuktiGuru" 
                                                    data-foto-url="{{ asset($item->photo_path) }}"
                                                    data-mapel="{{ $item->jadwal?->mataPelajaran?->nama_mapel ?? '-' }}"
                                                    data-kelas="{{ $item->jadwal?->kelas?->nama_kelas ?? '-' }}"
                                                    data-tanggal="{{ $item->date ? \Carbon\Carbon::parse($item->date)->translatedFormat('d F Y') : '-' }}"
                                                    data-materi="{{ $item->topic_description ?? '-' }}"
                                                    title="Lihat Foto Bukti">
                                                <i class="fa-regular fa-image me-1"></i> Foto
                                            </button>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-outline-primary btn-sm py-1 px-2 d-inline-flex align-items-center gap-1 shadow-sm"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditJurnal{{ $item->id }}" 
                                                title="Edit Jurnal & Presensi">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span>Edit</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-clipboard fa-3x mb-2 text-secondary d-block"></i>
                                        Belum ada rekaman jurnal mengajar dari akun Anda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================== --}}
{{-- MODAL-MODAL RIWAYAT JURNAL (Di luar tabel agar bebas stacking context) --}}
{{-- ========================================================== --}}
@foreach($jurnalList as $item)
    @php
        $absenTidakHadir = $item->attendances->where('status', '!=', 'Hadir');
    @endphp

    {{-- 1. Modal Detail Absensi --}}
    @if($absenTidakHadir->count() > 0)
        <div class="modal fade" id="modalAbsensi{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow border-0" style="border-radius: var(--radius); overflow: hidden;">
                    <div class="modal-header bg-dark text-white py-2 px-3">
                        <h6 class="modal-title mb-0 small fw-bold">
                            <i class="fa-solid fa-clipboard-user me-2 text-warning"></i>
                            Detail Siswa Tidak Masuk — {{ $item->date ? \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') : '-' }}
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                            <small class="text-primary fw-bold">
                                <i class="fa-solid fa-school me-1"></i>
                                {{ $item->jadwal?->kelas?->nama_kelas ?? '-' }} — {{ $item->jadwal?->mataPelajaran?->nama_mapel ?? '-' }}
                            </small>
                            <span class="badge bg-danger">{{ $absenTidakHadir->count() }} Siswa</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">No</th>
                                        <th>Nama Siswa</th>
                                        <th class="text-center">Status</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($absenTidakHadir as $att)
                                        <tr>
                                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $att->siswa?->nama_siswa ?? 'Siswa Tidak Ditemukan' }}</div>
                                                <small class="text-muted font-monospace">{{ $att->siswa?->nisn ?? '-' }}</small>
                                            </td>
                                            <td class="text-center">
                                                @if($att->status === 'Sakit')
                                                    <span class="badge bg-info text-white">Sakit</span>
                                                @elseif($att->status === 'Izin')
                                                    <span class="badge bg-warning text-dark">Izin</span>
                                                @elseif($att->status === 'Alfa')
                                                    <span class="badge bg-danger">Alfa</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $att->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $att->keterangan ?: '-' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer py-1 px-3 bg-light">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 2. Modal Edit Jurnal Mengajar --}}
    <div class="modal fade text-start" id="modalEditJurnal{{ $item->id }}" tabindex="-1" aria-labelledby="modalEditJurnalLabel{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content shadow border-0" style="border-radius: var(--radius); overflow: hidden;">
                <form action="{{ route('jurnal.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="modal-header py-3 px-4 text-white" style="background: var(--color-dark, #0f2a5c);">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark p-2 rounded-3">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </span>
                            <div>
                                <h6 class="modal-title mb-0 fw-bold" id="modalEditJurnalLabel{{ $item->id }}">Edit Jurnal Mengajar & Presensi</h6>
                                <small class="text-white-50">
                                    {{ $item->jadwal?->kelas?->nama_kelas ?? ($item->jadwal?->id_kelas ? 'Kelas '.$item->jadwal->id_kelas : '-') }} 
                                    • {{ $item->jadwal?->mataPelajaran?->nama_mapel ?? ($item->jadwal?->id_mapel ? 'Mapel '.$item->jadwal->id_mapel : '-') }}
                                </small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        {{-- Info Sesi Jadwal (Readonly) --}}
                        <div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 mb-3 rounded-3">
                            <div>
                                <span class="badge bg-primary me-2">Jadwal</span>
                                <strong class="text-dark">{{ $item->jadwal?->kelas?->nama_kelas ?? '-' }}</strong> — {{ $item->jadwal?->mataPelajaran?->nama_mapel ?? '-' }}
                            </div>
                            <div class="small text-muted d-flex flex-wrap gap-2">
                                <span><i class="fa-regular fa-calendar me-1"></i> {{ $item->jadwal?->hari ?? '-' }}</span>
                                <span><i class="fa-regular fa-clock me-1"></i> {{ $item->jadwal?->label_sesi ? $item->jadwal->label_sesi . ' (' : '' }}{{ $item->jadwal?->jam_mulai ? \Carbon\Carbon::parse($item->jadwal->jam_mulai)->format('H:i') : '--:--' }} - {{ $item->jadwal?->jam_selesai ? \Carbon\Carbon::parse($item->jadwal->jam_selesai)->format('H:i') : '--:--' }}{{ $item->jadwal?->label_sesi ? ')' : '' }}</span>
                                <span><i class="fa-solid fa-door-open me-1"></i> {{ $item->jadwal?->ruangan?->nama_ruangan ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            {{-- Tanggal KBM --}}
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark" for="edit_date_{{ $item->id }}">
                                    <i class="fa-regular fa-calendar me-1 text-primary"></i> Tanggal KBM <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="date" id="edit_date_{{ $item->id }}" class="form-control" value="{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : date('Y-m-d') }}" required>
                            </div>

                            {{-- Bukti Foto --}}
                            <div class="col-md-7">
                                <label class="form-label fw-bold small text-dark">
                                    <i class="fa-solid fa-camera me-1 text-primary"></i> Bukti Foto KBM (Wajib Kamera Langsung)
                                </label>
                                <div class="p-2 border rounded bg-light">
                                    @if($item->photo_path)
                                        <div class="d-flex align-items-center gap-2 mb-2 p-1 bg-white border rounded">
                                            <img src="{{ asset($item->photo_path) }}" alt="Foto Tersimpan" class="rounded border" style="width: 44px; height: 44px; object-fit: cover;">
                                            <div class="small">
                                                <span class="text-dark fw-semibold d-block">Foto KBM saat ini tersimpan</span>
                                                <a href="{{ asset($item->photo_path) }}" target="_blank" class="text-primary text-decoration-none">
                                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Lihat foto asli
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                    <input type="hidden" name="photo_base64" id="edit_photo_base64_{{ $item->id }}">
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100 py-1 fw-semibold" onclick="triggerDeviceCameraForEdit({{ $item->id }})">
                                            <i class="fa-solid fa-camera me-1"></i> Ambil Foto Kamera
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" onclick="openCameraModalForEdit({{ $item->id }})" title="Gunakan live viewfinder webcam">
                                            <i class="fa-solid fa-video"></i>
                                        </button>
                                    </div>
                                    <div id="edit_photo_preview_box_{{ $item->id }}" class="mt-2 d-none text-center p-2 bg-white rounded border">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle mb-1"><i class="fa-solid fa-check me-1"></i> Foto baru siap disimpan</span>
                                        <img id="edit_preview_img_{{ $item->id }}" src="#" class="img-thumbnail rounded d-block mx-auto" style="max-height: 90px; object-fit: cover;">
                                    </div>
                                    <div class="form-text small text-muted mt-1" style="font-size: 0.72rem;">
                                        Wajib mengambil foto langsung dari kamera jika ingin memperbarui dokumentasi.
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Topik / Rangkuman Materi --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-dark" for="edit_topic_{{ $item->id }}">
                                <i class="fa-solid fa-book-open-reader me-1 text-primary"></i> Topik / Rangkuman Materi Pembelajaran <span class="text-danger">*</span>
                            </label>
                            <textarea name="topic_description" id="edit_topic_{{ $item->id }}" rows="3" class="form-control" placeholder="Tuliskan materi pembelajaran yang diajarkan..." required>{{ $item->topic_description }}</textarea>
                        </div>

                        {{-- Presensi Siswa --}}
                        <div class="card border rounded-3 overflow-hidden">
                            <div class="card-header bg-light py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="fw-bold text-dark small">
                                    <i class="fa-solid fa-clipboard-user me-1 text-primary"></i> Presensi Kehadiran Siswa
                                </div>
                                <div class="small text-muted">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Centang = Tidak Hadir</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Tidak Dicentang = Hadir</span>
                                </div>
                            </div>

                            @php
                                $kelasSiswa = $siswaByKelas[$item->jadwal?->id_kelas] ?? collect();
                                if ($kelasSiswa->isEmpty()) {
                                    $kelasSiswa = $semuaSiswa ?? collect();
                                }
                                $itemAtts = $item->attendances->keyBy('student_id');
                            @endphp

                            <div class="p-2 border-bottom bg-white">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" class="form-control" placeholder="Cari nama atau NISN siswa..." onkeyup="filterEditSiswa({{ $item->id }}, this.value)">
                                </div>
                            </div>

                            <div class="card-body p-2" style="max-height: 280px; overflow-y: auto;">
                                @if($kelasSiswa->isEmpty())
                                    <div class="text-center py-4 text-muted small">
                                        <i class="fa-solid fa-users-slash me-1 fa-2x d-block mb-1 text-secondary"></i>
                                        Data siswa tidak ditemukan untuk kelas ini.
                                    </div>
                                @else
                                    <div class="list-group list-group-flush">
                                        @foreach($kelasSiswa as $sIndex => $s)
                                            @php
                                                $att = $itemAtts->get($s->id_siswa);
                                                $isAbsen = !is_null($att);
                                                $statusAbsen = $att ? $att->status : 'Alfa';
                                                $ketAbsen = $att ? $att->keterangan : '';
                                                $sParts = explode(' ', trim($s->nama_siswa ?? 'S'));
                                                $sInit = strtoupper(substr($sParts[0] ?? 'S', 0, 1) . substr($sParts[1] ?? '', 0, 1));
                                            @endphp
                                            <div class="list-group-item p-2 border-bottom edit-siswa-item-{{ $item->id }}" data-nama="{{ strtolower($s->nama_siswa) }}" data-nisn="{{ $s->nisn }}">
                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                                                        <input class="form-check-input mt-0 edit-absen-chk" type="checkbox" 
                                                               id="edit_chk_{{ $item->id }}_{{ $s->id_siswa }}" 
                                                               {{ $isAbsen ? 'checked' : '' }}
                                                               onchange="toggleEditAbsensiRow({{ $item->id }}, {{ $s->id_siswa }}, this)">
                                                        <div class="siswa-avatar-initial" style="width: 28px; height: 28px; min-width: 28px; font-size: 0.68rem;">
                                                            {{ $sInit ?: 'S' }}
                                                        </div>
                                                        <div class="text-truncate">
                                                            <label class="form-check-label fw-semibold text-dark mb-0 small text-truncate d-block cursor-pointer" for="edit_chk_{{ $item->id }}_{{ $s->id_siswa }}">
                                                                {{ $s->nama_siswa }}
                                                            </label>
                                                            <small class="text-muted font-monospace" style="font-size: 0.68rem;">NISN: {{ $s->nisn ?? '-' }}</small>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="badge {{ $isAbsen ? ($statusAbsen == 'Sakit' ? 'bg-info text-white' : ($statusAbsen == 'Izin' ? 'bg-warning text-dark' : 'bg-danger text-white')) : 'bg-success text-white' }}" 
                                                              id="edit_badge_{{ $item->id }}_{{ $s->id_siswa }}" 
                                                              style="font-size: 0.7rem;">
                                                            {{ $isAbsen ? $statusAbsen : 'Hadir' }}
                                                        </span>
                                                    </div>
                                                </div>

                                                {{-- Form Input Status & Ket jika Tidak Hadir --}}
                                                <div class="edit-absen-fields mt-2 ps-4" id="edit_fields_{{ $item->id }}_{{ $s->id_siswa }}" style="display: {{ $isAbsen ? 'block' : 'none' }};">
                                                    <input type="hidden" name="absensi[{{ $sIndex }}][student_id]" value="{{ $s->id_siswa }}" {{ $isAbsen ? '' : 'disabled' }} id="edit_sid_{{ $item->id }}_{{ $s->id_siswa }}">
                                                    <div class="row g-2">
                                                        <div class="col-sm-4">
                                                            <select name="absensi[{{ $sIndex }}][status]" class="form-select form-select-sm fw-semibold" {{ $isAbsen ? '' : 'disabled' }} id="edit_status_{{ $item->id }}_{{ $s->id_siswa }}" onchange="updateEditBadge({{ $item->id }}, {{ $s->id_siswa }}, this.value)">
                                                                <option value="Sakit" {{ $statusAbsen === 'Sakit' ? 'selected' : '' }}>Sakit</option>
                                                                <option value="Izin" {{ $statusAbsen === 'Izin' ? 'selected' : '' }}>Izin</option>
                                                                <option value="Alfa" {{ $statusAbsen === 'Alfa' ? 'selected' : '' }}>Alfa</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-sm-8">
                                                            <input type="text" name="absensi[{{ $sIndex }}][keterangan]" class="form-control form-control-sm" placeholder="Keterangan / alasan (opsional)..." value="{{ $ketAbsen }}" {{ $isAbsen ? '' : 'disabled' }} id="edit_ket_{{ $item->id }}_{{ $s->id_siswa }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer py-2 px-4 bg-light d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan Jurnal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- Modal Pratinjau Foto Bukti KBM (Tidak Berpindah Halaman) --}}
<div class="modal fade" id="modalFotoBuktiGuru" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow border-0" style="border-radius: var(--radius); overflow: hidden;">
            <div class="modal-header py-2 px-3 text-white" style="background: var(--color-dark);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-camera text-primary"></i>
                    <div>
                        <h6 class="modal-title mb-0 fw-bold" style="font-size: 0.92rem;">Bukti Dokumentasi Kegiatan KBM</h6>
                        <small class="text-white-50" id="guruFotoSubtitle" style="font-size: 0.75rem;">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center bg-dark position-relative d-flex align-items-center justify-content-center" style="min-height: 320px; max-height: 75vh; overflow: hidden;">
                <img id="guruFotoImg" src="" alt="Bukti Foto KBM" class="img-fluid" style="max-height: 75vh; width: auto; object-fit: contain;">
            </div>
            <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                <div class="text-start pe-3 text-truncate" style="max-width: 65%;">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">Topik Pembelajaran:</small>
                    <span class="text-dark small fw-semibold text-truncate d-inline-block" id="guruFotoMateri" style="max-width: 100%;">-</span>
                </div>
                <div class="d-flex gap-2">
                    <a id="guruFotoDownload" href="#" target="_blank" download class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-download me-1"></i> Buka Gambar
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================== --}}
{{-- MODAL KAMERA INTERAKTIF (AMBIL FOTO DOKUMENTASI LANGSUNG)  --}}
{{-- ========================================================== --}}
<div class="modal fade" id="modalCameraCapture" tabindex="-1" aria-labelledby="modalCameraCaptureLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; background: #0f172a;">
            <div class="modal-header border-0 py-3 px-4" style="background: #1e293b;">
                <div class="d-flex align-items-center gap-2 text-white">
                    <span class="rounded-circle bg-danger d-inline-block" style="width: 10px; height: 10px; box-shadow: 0 0 8px #ef4444;"></span>
                    <h5 class="modal-title fw-bold fs-6 mb-0" id="modalCameraCaptureLabel">
                        <i class="fa-solid fa-camera me-1 text-primary"></i> Kamera Dokumentasi KBM
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light d-none" id="btnSwitchCamera" onclick="switchCamera()" title="Ganti Kamera (Depan / Belakang)">
                        <i class="fa-solid fa-camera-rotate me-1"></i> Ganti Kamera
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopCameraStream()"></button>
                </div>
            </div>

            <div class="modal-body p-0 bg-black text-center position-relative" style="min-height: 380px;">
                {{-- Live Video Stream --}}
                <div id="cameraStreamContainer" class="position-relative d-flex justify-content-center align-items-center bg-black" style="min-height: 380px;">
                    <video id="cameraVideo" autoplay playsinline muted style="width: 100%; max-height: 480px; object-fit: contain; background: #000;"></video>
                    
                    {{-- Guide Overlay --}}
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-between p-3" style="pointer-events: none;">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-dark bg-opacity-75 text-white small px-2 py-1">
                                <i class="fa-solid fa-circle text-danger me-1" style="font-size: 0.6rem;"></i> Live Kamera
                            </span>
                            <span class="badge bg-dark bg-opacity-75 text-white-50 small" id="cameraTimestamp"></span>
                        </div>
                        <div class="text-center">
                            <span class="badge bg-dark bg-opacity-75 text-white small px-3 py-1 rounded-pill">
                                Arahkan kamera ke suasana kegiatan belajar di kelas
                            </span>
                        </div>
                    </div>

                    {{-- Loading Spinner --}}
                    <div id="cameraLoading" class="position-absolute top-50 start-50 translate-middle text-white text-center">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <p class="small mb-0 text-white-50">Menghubungkan ke kamera perangkat...</p>
                    </div>

                    {{-- Error Box (Jika Akses Kamera Ditolak / Tidak Ditemukan) --}}
                    <div id="cameraErrorBox" class="position-absolute top-50 start-50 translate-middle w-75 d-none">
                        <div class="alert alert-danger bg-danger text-white border-0 shadow-lg p-4 rounded-3 text-center">
                            <i class="fa-solid fa-triangle-exclamation fa-2x mb-2 d-block"></i>
                            <h6 class="fw-bold mb-1" id="cameraErrorTitle">Kamera Tidak Dapat Dibuka</h6>
                            <p class="small mb-3 text-white-50" id="cameraErrorMessage">
                                Pastikan izin akses kamera telah diizinkan pada browser Anda. Pengambilan foto wajib menggunakan kamera langsung perangkat.
                            </p>
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                <button type="button" class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-xs" onclick="triggerDeviceCamera()">
                                    <i class="fa-solid fa-camera me-1"></i> Buka Kamera Bawaan HP / Laptop
                                </button>
                                <button type="button" class="btn btn-outline-light btn-sm px-3 py-2 shadow-xs" onclick="startCameraStream(currentFacingMode)">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Coba Hubungkan Lagi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Freeze Frame Canvas (Hidden) --}}
                <canvas id="cameraCanvas" class="d-none"></canvas>

                {{-- Snapshot Result Preview (Setelah Dijepret) --}}
                <div id="snapshotPreviewContainer" class="d-none position-relative bg-black" style="min-height: 380px;">
                    <img id="snapshotImg" src="#" alt="Hasil Jepretan Kamera" style="width: 100%; max-height: 480px; object-fit: contain;">
                    <div class="position-absolute top-0 end-0 p-3">
                        <span class="badge bg-success p-2 shadow-sm fs-7">
                            <i class="fa-solid fa-check me-1"></i> Foto Siap Digunakan
                        </span>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 py-3 px-4 d-flex justify-content-between align-items-center" style="background: #1e293b;">
                {{-- State Viewfinder: Tombol Jepret --}}
                <div id="viewfinderControls" class="w-100 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-light btn-sm px-3" data-bs-dismiss="modal" onclick="stopCameraStream()">
                        Batal
                    </button>
                    
                    {{-- Shutter Button --}}
                    <button type="button" id="btnShutter" class="btn btn-danger rounded-circle p-0 shadow-lg d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; border: 4px solid rgba(255,255,255,0.4);" onclick="takeSnapshot()" title="Jepret Foto">
                        <i class="fa-solid fa-camera fs-4 text-white"></i>
                    </button>

                    <button type="button" class="btn btn-outline-light btn-sm px-2" onclick="triggerDeviceCamera()" title="Gunakan aplikasi kamera bawaan perangkat">
                        <i class="fa-solid fa-mobile-screen me-1"></i> Kamera Bawaan
                    </button>
                </div>

                {{-- State Hasil Jepret: Ambil Ulang atau Gunakan --}}
                <div id="snapshotControls" class="w-100 d-none justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-light" onclick="retakePhoto()">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> Ambil Ulang
                    </button>
                    <button type="button" class="btn btn-success fw-bold px-4 py-2 shadow-sm" onclick="applySnapshotToForm()">
                        <i class="fa-solid fa-check me-1"></i> Gunakan Foto Ini
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleSelect = document.getElementById('schedule_id');
    const daftarSiswaContainer = document.getElementById('daftarSiswaContainer');
    const absensiLoading = document.getElementById('absensiLoading');
    const absensiPlaceholder = document.getElementById('absensiPlaceholder');
    const badgeJumlahSiswa = document.getElementById('badgeJumlahSiswa');
    const filterSiswaBox = document.getElementById('filterSiswaBox');
    const labelKelasAktif = document.getElementById('labelKelasAktif');
    
    // Preview elemen detail jadwal
    const jadwalDetailBox = document.getElementById('jadwalDetailBox');
    const boxKelasMapel = document.getElementById('boxKelasMapel');
    const boxHariJam = document.getElementById('boxHariJam');
    const boxRuangan = document.getElementById('boxRuangan');
    const boxCycle = document.getElementById('boxCycle');

    // Textarea counter
    const topicTextarea = document.getElementById('topic_description');
    const charCount = document.getElementById('charCount');
    if (topicTextarea && charCount) {
        topicTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length + ' karakter';
        });
        charCount.textContent = topicTextarea.value.length + ' karakter';
    }

    // Photo preview handler
    const photoInput = document.getElementById('photo');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const uploadPreview = document.getElementById('uploadPreview');
    const previewImg = document.getElementById('previewImg');
    const previewFileName = document.getElementById('previewFileName');

    // Modal Foto Bukti Viewer
    const modalFotoGuru = document.getElementById('modalFotoBuktiGuru');
    if (modalFotoGuru) {
        modalFotoGuru.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            const fotoUrl = button.getAttribute('data-foto-url');
            const mapel = button.getAttribute('data-mapel');
            const kelas = button.getAttribute('data-kelas');
            const tanggal = button.getAttribute('data-tanggal');
            const materi = button.getAttribute('data-materi');

            document.getElementById('guruFotoImg').src = fotoUrl || '';
            document.getElementById('guruFotoDownload').href = fotoUrl || '#';
            document.getElementById('guruFotoSubtitle').textContent = (kelas || '-') + ' (' + (mapel || '-') + ') • ' + (tanggal || '-');
            document.getElementById('guruFotoMateri').textContent = materi || '-';
        });
    }

    // Listener tangkapan kamera langsung bawaan perangkat (Android, iOS, PC/Laptop)
    const directCamInput = document.getElementById('directCameraCapture');
    if (directCamInput) {
        directCamInput.addEventListener('change', function(e) {
            if (!this.files || !this.files[0]) return;
            const file = this.files[0];
            const reader = new FileReader();
            reader.onload = function(evt) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.getElementById('cameraCanvas') || document.createElement('canvas');
                    const maxDim = 1280;
                    let w = img.width;
                    let h = img.height;
                    if (w > maxDim || h > maxDim) {
                        if (w > h) {
                            h = Math.round((h * maxDim) / w);
                            w = maxDim;
                        } else {
                            w = Math.round((w * maxDim) / h);
                            h = maxDim;
                        }
                    }
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);

                    // Tambahkan Watermark Resmi Waktu & Tanggal KBM
                    const now = new Date();
                    const dateStr = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
                    const watermarkText = 'DOKUMENTASI KBM • ' + dateStr + ' ' + timeStr;

                    ctx.font = 'bold ' + Math.max(14, Math.floor(w / 40)) + 'px sans-serif';
                    ctx.fillStyle = 'rgba(0, 0, 0, 0.65)';
                    const textWidth = ctx.measureText(watermarkText).width;
                    const padding = 12;
                    ctx.fillRect(w - textWidth - (padding * 2) - 10, h - 38, textWidth + (padding * 2), 28);
                    ctx.fillStyle = '#ffffff';
                    ctx.fillText(watermarkText, w - textWidth - padding - 10, h - 18);

                    capturedDataUrl = canvas.toDataURL('image/jpeg', 0.88);
                    applySnapshotToForm();
                };
                img.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    // Validasi submit form jurnal: Wajib ada foto kamera & tidak boleh kedaluwarsa 5 jam
    const formJurnal = document.getElementById('formJurnal');
    if (formJurnal) {
        formJurnal.addEventListener('submit', function(e) {
            const photoBase64 = document.getElementById('photo_base64');
            const schedSelect = document.getElementById('schedule_id');
            const selectedOpt = schedSelect ? schedSelect.options[schedSelect.selectedIndex] : null;

            if (selectedOpt && selectedOpt.dataset.expired === '1') {
                e.preventDefault();
                alert('Pengisian ditolak! Sesi jadwal ini telah melewati batas 5 jam setelah jam pembelajaran selesai.');
                return false;
            }

            if (!photoBase64 || !photoBase64.value) {
                e.preventDefault();
                alert('Foto dokumentasi KBM wajib diambil langsung dari kamera saat kegiatan belajar mengajar!');
                triggerDeviceCamera();
                return false;
            }
        });
    }

    // Matikan stream kamera secara otomatis jika modal ditutup melalui tombol esc / klik luar
    const modalCameraEl = document.getElementById('modalCameraCapture');
    if (modalCameraEl) {
        modalCameraEl.addEventListener('hidden.bs.modal', function() {
            stopCameraStream();
        });
    }

    // Data siswa dari database yang di-preload oleh controller
    const siswaByKelasDb = @json($siswaByKelas ?? []);
    const semuaSiswaDb = @json($semuaSiswa ?? []);

    function renderDaftarSiswa(siswaList) {
        if (absensiLoading) absensiLoading.classList.add('d-none');

        if (!siswaList || siswaList.length === 0) {
            badgeJumlahSiswa.textContent = '0 Siswa';
            filterSiswaBox.style.display = 'none';
            daftarSiswaContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fa-solid fa-users-slash fa-2x d-block mb-2 text-warning"></i>
                    <h6 class="text-dark fw-bold mb-1">Belum Ada Siswa Terdaftar</h6>
                    <p class="text-muted small mb-0">Tidak ada data siswa aktif pada kelas ini di database.</p>
                </div>
            `;
            daftarSiswaContainer.style.display = 'block';
            resetLiveCounters();
            return;
        }

        badgeJumlahSiswa.textContent = siswaList.length + ' Siswa';
        filterSiswaBox.style.display = 'block';

        let html = `<div id="siswaList" class="siswa-presensi-list">`;
        
        siswaList.forEach(function(siswa, index) {
            const parts = (siswa.nama_siswa || '').trim().split(' ');
            const initial = (parts[0]?.[0] || '') + (parts[1]?.[0] || '');
            const isApproved = !!siswa.is_approved_leave;
            const leaveStatus = siswa.leave_status || 'Izin';
            const leaveReason = siswa.leave_reason || '';
            const autoKet = isApproved ? `Izin disetujui Wali Kelas/BK: ${leaveReason}` : '';

            html += `
            <div class="siswa-presensi-card" id="siswaRow_${siswa.id_siswa}" data-nama="${(siswa.nama_siswa || '').toLowerCase()}" data-nisn="${siswa.nisn || ''}">
                <div class="d-flex align-items-center justify-content-between p-2">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden" onclick="triggerCheckbox(${siswa.id_siswa})" style="cursor: pointer;">
                        <div class="form-check m-0" onclick="event.stopPropagation()">
                            <input class="form-check-input absensi-checkbox" type="checkbox" 
                                   id="chk_${siswa.id_siswa}" 
                                   data-siswa-id="${siswa.id_siswa}"
                                   ${isApproved ? 'checked' : ''}
                                   onchange="toggleAbsensiRow(this, ${siswa.id_siswa})">
                        </div>
                        <div class="siswa-avatar-initial">
                            ${initial.toUpperCase() || 'S'}
                        </div>
                        <div class="text-truncate">
                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.85rem;">
                                ${siswa.nama_siswa}
                            </div>
                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">
                                NISN: ${siswa.nisn || '-'}
                            </small>
                            ${isApproved ? `
                            <div class="mt-1">
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-circle-check me-1"></i> Disetujui Wali Kelas/BK: "${leaveReason}"
                                </span>
                            </div>` : ''}
                        </div>
                    </div>
                    <div>
                        ${isApproved ? `
                        <span class="badge ${leaveStatus === 'Sakit' ? 'bg-info-soft text-info border border-info-subtle' : 'bg-warning-soft text-warning-emphasis border border-warning-subtle'} absensi-status-badge" id="statusBadge_${siswa.id_siswa}" style="font-size: 0.7rem; padding: 0.35rem 0.6rem;">
                            <i class="fa-solid ${leaveStatus === 'Sakit' ? 'fa-briefcase-medical' : 'fa-envelope-open-text'} me-1"></i> ${leaveStatus} (Disetujui BK)
                        </span>` : `
                        <span class="badge bg-success-soft text-success border border-success-subtle absensi-status-badge" id="statusBadge_${siswa.id_siswa}" style="font-size: 0.7rem; padding: 0.35rem 0.6rem;">
                            <i class="fa-solid fa-check me-1"></i> Hadir
                        </span>`}
                    </div>
                </div>

                {{-- Expanded detail ketika dicentang Tidak Masuk --}}
                <div class="absensi-detail-box p-2 border-top bg-light" id="absensiDetail_${siswa.id_siswa}" style="display: ${isApproved ? 'block' : 'none'};">
                    <input type="hidden" name="absensi[${index}][student_id]" value="${siswa.id_siswa}" ${isApproved ? '' : 'disabled'} id="inputSiswaId_${siswa.id_siswa}">
                    <div class="row g-2">
                        <div class="col-sm-5">
                            <select class="form-select form-select-sm absensi-status-select fw-semibold" 
                                    name="absensi[${index}][status]" 
                                    ${isApproved ? '' : 'disabled'} 
                                    id="selectStatus_${siswa.id_siswa}"
                                    onchange="updateStatusBadge(${siswa.id_siswa}, this.value)">
                                <option value="Sakit" ${isApproved && leaveStatus === 'Sakit' ? 'selected' : ''}>Sakit</option>
                                <option value="Izin" ${isApproved && leaveStatus === 'Izin' ? 'selected' : ''}>Izin</option>
                                <option value="Alfa" ${!isApproved ? 'selected' : ''}>Alfa</option>
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <input type="text" class="form-control form-control-sm" 
                                   name="absensi[${index}][keterangan]" 
                                   placeholder="Alasan / ket (opsional)..."
                                   value="${autoKet}"
                                   ${isApproved ? '' : 'disabled'} 
                                   id="inputKet_${siswa.id_siswa}">
                        </div>
                    </div>
                </div>
            </div>`;
        });

        html += `</div>`;
        daftarSiswaContainer.innerHTML = html;
        daftarSiswaContainer.style.display = 'block';

        updateRingkasan();
    }

    function loadSiswaForSchedule(scheduleId) {
        if (!scheduleId) {
            if (jadwalDetailBox) jadwalDetailBox.style.display = 'none';
            if (absensiLoading) absensiLoading.style.display = 'none';
            if (daftarSiswaContainer) daftarSiswaContainer.style.display = 'none';
            if (filterSiswaBox) filterSiswaBox.style.display = 'none';
            if (badgeJumlahSiswa) badgeJumlahSiswa.textContent = '0 Siswa';
            if (labelKelasAktif) labelKelasAktif.textContent = '-';
            resetLiveCounters();
            return;
        }

        const selectedOpt = scheduleSelect.options[scheduleSelect.selectedIndex];
        if (selectedOpt && jadwalDetailBox) {
            if (boxKelasMapel) boxKelasMapel.textContent = (selectedOpt.dataset.kelas || '-') + ' — ' + (selectedOpt.dataset.mapel || '-');
            if (boxHariJam) boxHariJam.textContent = (selectedOpt.dataset.hari || '-') + ', ' + (selectedOpt.dataset.jam || '-');
            if (boxRuangan) boxRuangan.innerHTML = '<i class="fa-solid fa-door-open me-1"></i> Ruang: ' + (selectedOpt.dataset.ruangan || '-');
            if (boxCycle) boxCycle.innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i> ' + (selectedOpt.dataset.cycle || 'Putaran');
            jadwalDetailBox.style.display = 'block';
            if (labelKelasAktif) labelKelasAktif.textContent = selectedOpt.dataset.kelas || '-';

            // Cek status kedaluwarsa 5 jam setelah jam pembelajaran selesai
            const isExpired = selectedOpt.dataset.expired === '1';
            const expiredReason = selectedOpt.dataset.expiredReason || '';
            const deadlineVal = selectedOpt.dataset.deadline || '-';
            const alertExpired = document.getElementById('alertScheduleExpired');
            const textExpired = document.getElementById('textExpiredReason');
            const textDeadline = document.getElementById('textDeadlineVal');
            const btnSubmit = document.getElementById('btnSubmitJurnal');

            if (textDeadline) textDeadline.textContent = deadlineVal;

            if (isExpired) {
                if (alertExpired) alertExpired.classList.remove('d-none');
                if (textExpired) textExpired.textContent = expiredReason || 'Batas waktu pengisian jurnal maksimal 5 jam setelah jam pembelajaran selesai.';
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('btn-secondary');
                    btnSubmit.classList.remove('btn-primary');
                    btnSubmit.innerHTML = '<i class="fa-solid fa-ban me-2"></i> Pengisian Terkunci (> 5 Jam Selesai KBM)';
                }
            } else {
                if (alertExpired) alertExpired.classList.add('d-none');
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('btn-secondary');
                    btnSubmit.classList.add('btn-primary');
                    btnSubmit.innerHTML = '<i class="fa-solid fa-floppy-disk me-2"></i> Simpan Jurnal & Presensi Siswa';
                }
            }
        }

        if (absensiPlaceholder) absensiPlaceholder.style.display = 'none';

        const kelasId = selectedOpt ? selectedOpt.dataset.kelasId : null;
        let siswaKelas = null;
        if (kelasId && siswaByKelasDb) {
            siswaKelas = siswaByKelasDb[kelasId] || siswaByKelasDb[String(kelasId)] || siswaByKelasDb[parseInt(kelasId)];
        }

        // 1. Prioritas Utama: Tampilkan langsung dari data database kelas yang telah di-preload!
        if (siswaKelas && siswaKelas.length > 0) {
            if (absensiLoading) absensiLoading.classList.add('d-none');
            renderDaftarSiswa(siswaKelas);
            return;
        }

        // 2. Jika belum ada di preload kelas spesifik, tampilkan spinner & lakukan fetch
        if (absensiLoading) absensiLoading.classList.remove('d-none');

        fetch(`{{ route('jurnal.getSiswa') }}?schedule_id=${scheduleId}`)
            .then(response => response.json())
            .then(data => {
                if (absensiLoading) absensiLoading.classList.add('d-none');
                if (data.siswa && data.siswa.length > 0) {
                    renderDaftarSiswa(data.siswa);
                } else {
                    renderDaftarSiswa([]);
                }
            })
            .catch(error => {
                if (absensiLoading) absensiLoading.classList.add('d-none');
                daftarSiswaContainer.innerHTML = `
                    <div class="text-center py-4 text-danger small">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> Gagal memuat siswa: ${error.message}
                    </div>
                `;
                daftarSiswaContainer.style.display = 'block';
            });
    }

    // Pasang event listener saat guru memilih sesi jadwal / kelas yang berbeda
    if (scheduleSelect) {
        scheduleSelect.addEventListener('change', function() {
            loadSiswaForSchedule(this.value);
        });

        // Panggil saat halaman pertama kali dibuka agar siswa langsung sesuai jadwal terpilih
        if (scheduleSelect.value) {
            loadSiswaForSchedule(scheduleSelect.value);
        }
    }

    // Hitung ringkasan awal kehadiran (termasuk siswa yang otomatis izin)
    updateRingkasan();
});

// ==========================================
// PENGELOLA KAMERA DOKUMENTASI KBM INTERAKTIF
// ==========================================
let currentCameraStream = null;
let currentFacingMode = 'environment'; // Utamakan kamera belakang di HP/Tablet
let cameraModalInstance = null;
let capturedBlob = null;
let capturedDataUrl = null;

function openCameraModal() {
    const modalEl = document.getElementById('modalCameraCapture');
    if (!modalEl) return;
    
    if (!cameraModalInstance) {
        cameraModalInstance = new bootstrap.Modal(modalEl);
    }
    cameraModalInstance.show();
    startCameraStream(currentFacingMode);
}

function startCameraStream(facingMode) {
    const video = document.getElementById('cameraVideo');
    const loading = document.getElementById('cameraLoading');
    const errorBox = document.getElementById('cameraErrorBox');
    const btnSwitch = document.getElementById('btnSwitchCamera');
    const btnShutter = document.getElementById('btnShutter');
    const streamContainer = document.getElementById('cameraStreamContainer');
    const snapshotContainer = document.getElementById('snapshotPreviewContainer');
    const viewfinderControls = document.getElementById('viewfinderControls');
    const snapshotControls = document.getElementById('snapshotControls');
    const tsBadge = document.getElementById('cameraTimestamp');

    stopCameraStream();

    if (streamContainer) streamContainer.classList.remove('d-none');
    if (snapshotContainer) snapshotContainer.classList.add('d-none');
    if (viewfinderControls) {
        viewfinderControls.classList.remove('d-none');
        viewfinderControls.classList.add('d-flex');
    }
    if (snapshotControls) {
        snapshotControls.classList.add('d-none');
        snapshotControls.classList.remove('d-flex');
    }

    if (loading) loading.classList.remove('d-none');
    if (errorBox) errorBox.classList.add('d-none');
    if (btnShutter) btnShutter.disabled = true;

    // Update timestamp live
    if (tsBadge) {
        const now = new Date();
        tsBadge.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError('Browser ini tidak mendukung akses kamera langsung (getUserMedia). Silakan gunakan alternatif kamera bawaan perangkat.');
        return;
    }

    const constraints = {
        video: {
            facingMode: { ideal: facingMode },
            width: { ideal: 1280 },
            height: { ideal: 720 }
        },
        audio: false
    };

    navigator.mediaDevices.getUserMedia(constraints)
        .then(function(stream) {
            currentCameraStream = stream;
            if (video) {
                video.srcObject = stream;
                video.onloadedmetadata = function() {
                    video.play();
                    if (loading) loading.classList.add('d-none');
                    if (btnShutter) btnShutter.disabled = false;
                    if (btnSwitch) btnSwitch.classList.remove('d-none');
                };
            }
        })
        .catch(function(err) {
            console.warn('Gagal dengan facingMode, coba kamera umum:', err);
            // Fallback: coba kamera default tanpa constraint khusus
            navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                .then(function(stream) {
                    currentCameraStream = stream;
                    if (video) {
                        video.srcObject = stream;
                        video.onloadedmetadata = function() {
                            video.play();
                            if (loading) loading.classList.add('d-none');
                            if (btnShutter) btnShutter.disabled = false;
                        };
                    }
                })
                .catch(function(finalErr) {
                    if (loading) loading.classList.add('d-none');
                    showCameraError('Akses kamera ditolak atau perangkat tidak terhubung: ' + (finalErr.message || finalErr.name));
                });
        });
}

function stopCameraStream() {
    if (currentCameraStream) {
        currentCameraStream.getTracks().forEach(function(track) {
            track.stop();
        });
        currentCameraStream = null;
    }
}

function switchCamera() {
    currentFacingMode = (currentFacingMode === 'environment') ? 'user' : 'environment';
    startCameraStream(currentFacingMode);
}

function takeSnapshot() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');
    const snapshotImg = document.getElementById('snapshotImg');
    const streamContainer = document.getElementById('cameraStreamContainer');
    const snapshotContainer = document.getElementById('snapshotPreviewContainer');
    const viewfinderControls = document.getElementById('viewfinderControls');
    const snapshotControls = document.getElementById('snapshotControls');

    if (!video || !video.videoWidth) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    
    // Gambar frame video ke canvas
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Tambahkan watermark tanggal & waktu KBM di pojok kanan bawah foto
    const now = new Date();
    const dateStr = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
    const watermarkText = 'DOKUMENTASI KBM • ' + dateStr + ' ' + timeStr;

    ctx.font = 'bold ' + Math.max(14, Math.floor(canvas.width / 42)) + 'px sans-serif';
    ctx.fillStyle = 'rgba(0, 0, 0, 0.65)';
    const textWidth = ctx.measureText(watermarkText).width;
    const padding = 12;
    ctx.fillRect(canvas.width - textWidth - (padding * 2) - 10, canvas.height - 40, textWidth + (padding * 2), 30);
    ctx.fillStyle = '#ffffff';
    ctx.fillText(watermarkText, canvas.width - textWidth - padding - 10, canvas.height - 19);

    capturedDataUrl = canvas.toDataURL('image/jpeg', 0.88);
    if (snapshotImg) snapshotImg.src = capturedDataUrl;

    canvas.toBlob(function(blob) {
        capturedBlob = blob;
    }, 'image/jpeg', 0.88);

    // Tampilkan pratinjau snapshot
    if (streamContainer) streamContainer.classList.add('d-none');
    if (snapshotContainer) snapshotContainer.classList.remove('d-none');
    if (viewfinderControls) {
        viewfinderControls.classList.add('d-none');
        viewfinderControls.classList.remove('d-flex');
    }
    if (snapshotControls) {
        snapshotControls.classList.remove('d-none');
        snapshotControls.classList.add('d-flex');
    }
}

function retakePhoto() {
    const streamContainer = document.getElementById('cameraStreamContainer');
    const snapshotContainer = document.getElementById('snapshotPreviewContainer');
    const viewfinderControls = document.getElementById('viewfinderControls');
    const snapshotControls = document.getElementById('snapshotControls');

    if (snapshotContainer) snapshotContainer.classList.add('d-none');
    if (streamContainer) streamContainer.classList.remove('d-none');
    if (snapshotControls) {
        snapshotControls.classList.add('d-none');
        snapshotControls.classList.remove('d-flex');
    }
    if (viewfinderControls) {
        viewfinderControls.classList.remove('d-none');
        viewfinderControls.classList.add('d-flex');
    }
}

let cameraTargetJournalId = null;

function triggerDeviceCamera() {
    stopCameraStream();
    if (cameraModalInstance) {
        cameraModalInstance.hide();
    }
    cameraTargetJournalId = null;
    const directCam = document.getElementById('directCameraCapture');
    if (directCam) {
        directCam.value = '';
        directCam.click();
    }
}

function triggerDeviceCameraForEdit(journalId) {
    stopCameraStream();
    if (cameraModalInstance) {
        cameraModalInstance.hide();
    }
    cameraTargetJournalId = journalId;
    const directCam = document.getElementById('directCameraCapture');
    if (directCam) {
        directCam.value = '';
        directCam.click();
    }
}

function openCameraModalForEdit(journalId) {
    cameraTargetJournalId = journalId;
    openCameraModal();
}

function applySnapshotToForm() {
    if (!capturedDataUrl) return;

    if (cameraTargetJournalId) {
        // Target adalah form edit modal jurnal tertentu
        const editBase64 = document.getElementById('edit_photo_base64_' + cameraTargetJournalId);
        const editPreviewBox = document.getElementById('edit_photo_preview_box_' + cameraTargetJournalId);
        const editPreviewImg = document.getElementById('edit_preview_img_' + cameraTargetJournalId);
        if (editBase64) editBase64.value = capturedDataUrl;
        if (editPreviewImg) editPreviewImg.src = capturedDataUrl;
        if (editPreviewBox) editPreviewBox.classList.remove('d-none');
        cameraTargetJournalId = null;
    } else {
        // Target adalah form jurnal utama
        const photoBase64 = document.getElementById('photo_base64');
        const previewImg = document.getElementById('previewImg');
        const previewFileName = document.getElementById('previewFileName');
        const uploadPrompt = document.getElementById('uploadPrompt');
        const uploadPreview = document.getElementById('uploadPreview');

        if (previewImg) previewImg.src = capturedDataUrl;
        if (photoBase64) photoBase64.value = capturedDataUrl;

        const fileName = 'foto_kbm_' + (new Date().toISOString().replace(/[:.]/g, '-')) + '.jpg';
        if (previewFileName) previewFileName.textContent = fileName + ' (Kamera Langsung)';

        if (uploadPrompt) uploadPrompt.classList.add('d-none');
        if (uploadPreview) uploadPreview.classList.remove('d-none');
    }

    stopCameraStream();
    if (cameraModalInstance) {
        cameraModalInstance.hide();
    }
}

function showCameraError(msg) {
    const errorBox = document.getElementById('cameraErrorBox');
    const errorMsg = document.getElementById('cameraErrorMessage');
    if (errorBox) errorBox.classList.remove('d-none');
    if (errorMsg) errorMsg.textContent = msg;
}

function clearPhotoUpload() {
    const photoBase64 = document.getElementById('photo_base64');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const uploadPreview = document.getElementById('uploadPreview');
    const previewImg = document.getElementById('previewImg');

    if (photoBase64) photoBase64.value = '';
    if (previewImg) previewImg.src = '#';
    if (uploadPrompt) uploadPrompt.classList.remove('d-none');
    if (uploadPreview) uploadPreview.classList.add('d-none');
    capturedBlob = null;
    capturedDataUrl = null;
}

function triggerCheckbox(siswaId) {
    const cb = document.getElementById('chk_' + siswaId);
    if (cb) {
        cb.checked = !cb.checked;
        toggleAbsensiRow(cb, siswaId);
    }
}

function toggleAbsensiRow(checkbox, siswaId) {
    const detail = document.getElementById('absensiDetail_' + siswaId);
    const row = document.getElementById('siswaRow_' + siswaId);
    const inputSiswaId = document.getElementById('inputSiswaId_' + siswaId);
    const selectStatus = document.getElementById('selectStatus_' + siswaId);
    const inputKet = document.getElementById('inputKet_' + siswaId);

    if (checkbox.checked) {
        detail.style.display = 'block';
        row.classList.add('is-tidak-hadir');
        inputSiswaId.disabled = false;
        selectStatus.disabled = false;
        inputKet.disabled = false;
        updateStatusBadge(siswaId, selectStatus.value);
    } else {
        detail.style.display = 'none';
        row.classList.remove('is-tidak-hadir');
        inputSiswaId.disabled = true;
        selectStatus.disabled = true;
        inputKet.disabled = true;
        
        const badge = document.getElementById('statusBadge_' + siswaId);
        badge.className = 'badge bg-success-soft text-success border border-success-subtle absensi-status-badge';
        badge.style.fontSize = '0.7rem';
        badge.innerHTML = '<i class="fa-solid fa-check me-1"></i> Hadir';
    }

    updateRingkasan();
}

function updateStatusBadge(siswaId, status) {
    const badge = document.getElementById('statusBadge_' + siswaId);
    badge.style.fontSize = '0.7rem';
    
    if (status === 'Sakit') {
        badge.className = 'badge bg-info-soft text-info border border-info-subtle absensi-status-badge';
        badge.innerHTML = '<i class="fa-solid fa-briefcase-medical me-1"></i> Sakit';
    } else if (status === 'Izin') {
        badge.className = 'badge bg-warning-soft text-warning-emphasis border border-warning-subtle absensi-status-badge';
        badge.innerHTML = '<i class="fa-solid fa-envelope me-1"></i> Izin';
    } else if (status === 'Alfa') {
        badge.className = 'badge bg-danger-soft text-danger border border-danger-subtle absensi-status-badge';
        badge.innerHTML = '<i class="fa-solid fa-xmark me-1"></i> Alfa';
    }

    updateRingkasan();
}

function updateRingkasan() {
    const allCheckboxes = document.querySelectorAll('.absensi-checkbox');
    const totalSiswa = allCheckboxes.length;
    
    if (totalSiswa === 0) {
        resetLiveCounters();
        return;
    }

    let countSakit = 0, countIzin = 0, countAlfa = 0;

    allCheckboxes.forEach(function(cb) {
        if (cb.checked) {
            const siswaId = cb.dataset.siswaId;
            const select = document.getElementById('selectStatus_' + siswaId);
            if (select) {
                if (select.value === 'Sakit') countSakit++;
                else if (select.value === 'Izin') countIzin++;
                else if (select.value === 'Alfa') countAlfa++;
            }
        }
    });

    const countHadir = totalSiswa - countSakit - countIzin - countAlfa;

    document.getElementById('countHadir').textContent = countHadir;
    document.getElementById('countSakit').textContent = countSakit;
    document.getElementById('countIzin').textContent = countIzin;
    document.getElementById('countAlfa').textContent = countAlfa;
}

function resetLiveCounters() {
    document.getElementById('countHadir').textContent = '0';
    document.getElementById('countSakit').textContent = '0';
    document.getElementById('countIzin').textContent = '0';
    document.getElementById('countAlfa').textContent = '0';
}

function filterSiswaList() {
    const query = (document.getElementById('searchSiswaInput').value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.siswa-presensi-card');
    rows.forEach(function(row) {
        const nama = row.getAttribute('data-nama') || '';
        const nisn = row.getAttribute('data-nisn') || '';
        if (nama.includes(query) || nisn.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function resetAllHadir() {
    const allCheckboxes = document.querySelectorAll('.absensi-checkbox');
    allCheckboxes.forEach(function(cb) {
        if (cb.checked) {
            cb.checked = false;
            const siswaId = cb.dataset.siswaId;
            toggleAbsensiRow(cb, siswaId);
        }
    });
    const searchInput = document.getElementById('searchSiswaInput');
    if (searchInput) {
        searchInput.value = '';
        filterSiswaList();
    }
}

// ── Handler untuk Modal Edit Jurnal ──
function toggleEditAbsensiRow(jurnalId, siswaId, checkbox) {
    const fields = document.getElementById('edit_fields_' + jurnalId + '_' + siswaId);
    const sid = document.getElementById('edit_sid_' + jurnalId + '_' + siswaId);
    const status = document.getElementById('edit_status_' + jurnalId + '_' + siswaId);
    const ket = document.getElementById('edit_ket_' + jurnalId + '_' + siswaId);
    const badge = document.getElementById('edit_badge_' + jurnalId + '_' + siswaId);

    if (checkbox.checked) {
        if (fields) fields.style.display = 'block';
        if (sid) sid.disabled = false;
        if (status) status.disabled = false;
        if (ket) ket.disabled = false;
        updateEditBadge(jurnalId, siswaId, status ? status.value : 'Alfa');
    } else {
        if (fields) fields.style.display = 'none';
        if (sid) sid.disabled = true;
        if (status) status.disabled = true;
        if (ket) ket.disabled = true;
        if (badge) {
            badge.className = 'badge bg-success text-white';
            badge.textContent = 'Hadir';
        }
    }
}

function updateEditBadge(jurnalId, siswaId, val) {
    const badge = document.getElementById('edit_badge_' + jurnalId + '_' + siswaId);
    if (!badge) return;
    if (val === 'Sakit') {
        badge.className = 'badge bg-info text-white';
        badge.textContent = 'Sakit';
    } else if (val === 'Izin') {
        badge.className = 'badge bg-warning text-dark';
        badge.textContent = 'Izin';
    } else {
        badge.className = 'badge bg-danger text-white';
        badge.textContent = 'Alfa';
    }
}

function filterEditSiswa(jurnalId, query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.edit-siswa-item-' + jurnalId);
    rows.forEach(function(row) {
        const name = (row.getAttribute('data-nama') || '').toLowerCase();
        const nisn = (row.getAttribute('data-nisn') || '').toLowerCase();
        if (name.includes(q) || nisn.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
@endpush

@push('styles')
<style>
/* ── Avatar Inisial Siswa ── */
.siswa-avatar-initial {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 50%;
    background: #e0f2fe;
    color: #0284c7;
    font-weight: 700;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #bae6fd;
}

/* ── List Presensi Siswa di Sebelah Kanan ── */
.siswa-presensi-list {
    max-height: 480px;
    overflow-y: auto;
    padding-right: 4px;
}

.siswa-presensi-list::-webkit-scrollbar {
    width: 5px;
}

.siswa-presensi-list::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}

.siswa-presensi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
    overflow: hidden;
}

.siswa-presensi-card:hover {
    border-color: #94a3b8;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.siswa-presensi-card.is-tidak-hadir {
    border-color: #fca5a5;
    background-color: #fffaf0;
    box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.15);
}

.absensi-detail-box {
    animation: fadeInSlide 0.2s ease-out;
}

@keyframes fadeInSlide {
    from {
        opacity: 0;
        transform: translateY(-4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Soft Badges */
.bg-success-soft {
    background-color: #ecfdf5 !important;
}
.bg-info-soft {
    background-color: #f0f9ff !important;
}
.bg-warning-soft {
    background-color: #fffbeb !important;
}
.bg-danger-soft {
    background-color: #fef2f2 !important;
}

/* Photo Drag & Drop area */
.photo-upload-container {
    transition: all 0.2s ease;
}
.photo-upload-container:hover {
    background: #f1f8fe !important;
    border-color: #2f9bf5 !important;
}

.cursor-pointer {
    cursor: pointer;
}

/* Pastikan modal selalu di atas backdrop dan dapat diklik secara interaktif */
.modal {
    z-index: 1060 !important;
}
.modal-backdrop {
    z-index: 1050 !important;
}
</style>
@endpush