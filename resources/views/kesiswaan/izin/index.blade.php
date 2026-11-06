@extends('layouts.app')

@section('title', 'Verifikasi Izin Siswa - Wali Kelas & BK - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Verifikasi Izin Siswa</h1>
        <p class="page-subtitle">Pemeriksaan dan persetujuan surat permohonan izin / sakit siswa oleh Wali Kelas & Guru BK</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('rekap.absensi.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-clipboard-user me-1"></i> Rekap Absensi Siswa
        </a>
        <span class="badge-soft badge-soft-info px-3 py-2">
            <i class="fa-solid fa-user-check me-1"></i>
            Wewenang: <strong>{{ session('id_role') == 7 ? 'Guru BK' : (session('id_role') == 3 ? 'Wali Kelas' : 'Wali Kelas & Guru BK') }}</strong>
        </span>
    </div>
</div>
@endsection

@section('content')
@php
    $totalCount    = count($pendingLeaves);
    $pendingCount  = $pendingLeaves->where('status_approval', 'Pending')->count();
    $approvedCount = $pendingLeaves->where('status_approval', 'Approved')->count();
    $rejectedCount = $pendingLeaves->where('status_approval', 'Rejected')->count();
@endphp

{{-- ===== BANNER KELAS WALI KELAS ===== --}}
@if($isWaliKelas)
    <div class="alert d-flex align-items-center gap-3 mb-4 border-0 shadow-sm py-3 px-4"
         style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 4px solid #10b981 !important; border-radius: 12px;">
        <div style="width:40px;height:40px;border-radius:10px;background:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fa-solid fa-user-tie text-white"></i>
        </div>
        <div>
            <div class="fw-bold text-success" style="font-size:0.95rem;">Tampilan Khusus Wali Kelas</div>
            @if($waliKelasLabel)
                <div class="text-dark small">
                    Menampilkan pengajuan izin siswa kelas:
                    <span class="badge bg-success text-white ms-1 px-2 py-1">
                        <i class="fa-solid fa-graduation-cap me-1"></i>{{ $waliKelasLabel }}
                    </span>
                </div>
            @else
                <div class="text-warning small">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Kelas tidak terdeteksi dari username. Menampilkan semua pengajuan.
                </div>
            @endif
        </div>
    </div>
@endif

{{-- ===== FILTER KELAS UNTUK GURU BK & WAKA ===== --}}
@if(!$isWaliKelas)
    <div class="app-card mb-4 py-3 px-4">
        <form method="GET" action="{{ url()->current() }}" class="d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <div style="width:36px;height:36px;border-radius:9px;background:rgba(14,165,233,.12);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-filter text-info"></i>
                </div>
                <span class="fw-semibold text-dark">Filter Kelas:</span>
            </div>
            <div class="flex-grow-1" style="min-width:200px;max-width:320px;">
                <select name="id_kelas" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— Semua Kelas —</option>
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id_kelas }}" {{ $selectedKelasId == $k->id_kelas ? 'selected' : '' }}>
                            {{ trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? $k->nama_jurusan ?? $k->id_kelas)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($selectedKelasId)
                <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-xmark me-1"></i>Reset Filter
                </a>
                @php
                    $kelasYangDipilih = $daftarKelas->firstWhere('id_kelas', $selectedKelasId);
                    $labelKelasFilter = $kelasYangDipilih
                        ? trim(($kelasYangDipilih->tingkat ?? '') . ' ' . ($kelasYangDipilih->id_jurusan ?? $kelasYangDipilih->nama_jurusan ?? $selectedKelasId))
                        : $selectedKelasId;
                @endphp
                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                    <i class="fa-solid fa-graduation-cap me-1"></i>Kelas: {{ $labelKelasFilter }}
                    &mdash; {{ $totalCount }} pengajuan
                </span>
            @endif
        </form>
    </div>
@endif

<!-- Stat Mini Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="app-card p-3 d-flex align-items-center gap-3">
            <div class="stat-icon-wrap" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Total Permohonan</div>
                <div class="fs-4 fw-bold text-dark">{{ $totalCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="app-card p-3 d-flex align-items-center gap-3">
            <div class="stat-icon-wrap" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Menunggu Verifikasi</div>
                <div class="fs-4 fw-bold text-warning">{{ $pendingCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="app-card p-3 d-flex align-items-center gap-3">
            <div class="stat-icon-wrap" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Telah Disetujui</div>
                <div class="fs-4 fw-bold text-success">{{ $approvedCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="app-card p-3 d-flex align-items-center gap-3">
            <div class="stat-icon-wrap" style="width: 44px; height: 44px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <div class="text-muted small fw-medium">Ditolak</div>
                <div class="fs-4 fw-bold text-danger">{{ $rejectedCount }}</div>
            </div>
        </div>
    </div>
</div>

<div class="app-card">
    <div class="app-card-header">
        <h5 class="app-card-title">
            <i class="fa-solid fa-envelope-open-text text-primary"></i>
            Daftar Pengajuan Surat Izin Siswa
        </h5>
        <span class="badge-soft badge-soft-secondary">
            Total: {{ $totalCount }} Permohonan
        </span>
    </div>
    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table app-table align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 45px;">No</th>
                        <th>Identitas Siswa</th>
                        <th>Rentang Waktu</th>
                        <th>Alasan Permohonan</th>
                        <th class="text-center">Lampiran</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 170px;">Aksi Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingLeaves as $index => $item)
                        <tr>
                            <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="fw-bold text-dark">{{ $item->student?->nama_siswa ?? 'Siswa Tidak Ditemukan' }}</span>
                                    @if($item->student?->kelas?->nama_kelas || $item->student?->id_kelas)
                                        <span class="badge bg-light text-primary border px-2 py-0.5 rounded-pill small">
                                            <i class="fa-solid fa-graduation-cap me-1"></i>{{ $item->student?->kelas?->nama_kelas ?? $item->student?->id_kelas }}
                                        </span>
                                    @endif
                                </div>
                                <small class="text-muted font-monospace">
                                    NISN: {{ $item->student?->nisn ?? '-' }}
                                </small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{ $item->start_date ? \Carbon\Carbon::parse($item->start_date)->translatedFormat('d M Y') : '-' }}
                                </div>
                                <small class="text-muted">
                                    s/d {{ $item->end_date ? \Carbon\Carbon::parse($item->end_date)->translatedFormat('d M Y') : '-' }}
                                </small>
                            </td>
                            <td>
                                <div class="small text-dark" style="max-width: 250px;">
                                    {{ $item->reason ?? '-' }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($item->document_path)
                                    @php
                                        $ext = strtolower(pathinfo($item->document_path, PATHINFO_EXTENSION));
                                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        $isPdf   = $ext === 'pdf';
                                    @endphp
                                    <button type="button"
                                        class="btn btn-outline-primary btn-sm btn-preview-berkas"
                                        title="Pratinjau Berkas Lampiran"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalPreviewBerkas"
                                        data-url="{{ asset($item->document_path) }}"
                                        data-type="{{ $isImage ? 'image' : ($isPdf ? 'pdf' : 'other') }}"
                                        data-nama="{{ $item->student?->nama_siswa ?? 'Siswa' }}"
                                        data-ext="{{ $ext }}">
                                        <i class="fa-solid fa-{{ $isImage ? 'image' : ($isPdf ? 'file-pdf' : 'file-arrow-down') }} me-1"></i>Berkas
                                    </button>
                                @else
                                    <span class="text-muted small">Tidak ada</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item->status_approval == 'Approved')
                                    <span class="badge-soft badge-soft-success">
                                        <i class="fa-solid fa-check"></i> Disetujui
                                    </span>
                                @elseif($item->status_approval == 'Rejected')
                                    <span class="badge-soft badge-soft-danger">
                                        <i class="fa-solid fa-xmark"></i> Ditolak
                                    </span>
                                @else
                                    <span class="badge-soft badge-soft-warning">
                                        <i class="fa-regular fa-clock"></i> Menunggu
                                    </span>
                                @endif

                                @if($item->verifier)
                                    <div class="mt-1" style="font-size: 0.72rem; color: #64748b;">
                                        <i class="fa-solid fa-user-check me-1"></i>{{ $item->verifier->nama_lengkap ?? $item->verifier->username }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <!-- Form Setujui -->
                                    <form action="{{ route('kesiswaan.izin.update', $item->id) }}" method="POST" class="d-inline m-0">
                                        @csrf
                                        <input type="hidden" name="status_approval" value="Approved">
                                        <button type="submit" class="btn btn-success btn-sm" {{ $item->status_approval == 'Approved' ? 'disabled' : '' }} onclick="return confirm('Setujui pengajuan izin ini?')" title="Setujui Izin">
                                            <i class="fa-solid fa-check"></i>
                                            <span class="d-none d-sm-inline">Setuju</span>
                                        </button>
                                    </form>

                                    <!-- Form Tolak -->
                                    <form action="{{ route('kesiswaan.izin.update', $item->id) }}" method="POST" class="d-inline m-0">
                                        @csrf
                                        <input type="hidden" name="status_approval" value="Rejected">
                                        <button type="submit" class="btn btn-danger btn-sm" {{ $item->status_approval == 'Rejected' ? 'disabled' : '' }} onclick="return confirm('Tolak permohonan izin ini?')" title="Tolak Izin">
                                            <i class="fa-solid fa-xmark"></i>
                                            <span class="d-none d-sm-inline">Tolak</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-envelope-open fa-2x mb-2 text-secondary d-block"></i>
                                Belum ada pengajuan izin dari siswa yang memerlukan verifikasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
{{-- ===================== MODAL PREVIEW BERKAS ===================== --}}
<div class="modal fade" id="modalPreviewBerkas" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">

            {{-- Header --}}
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 1rem 1.5rem;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-file-magnifying-glass text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 text-white fw-bold" id="modalPreviewLabel">Pratinjau Berkas</h5>
                        <small class="text-white opacity-75" id="modalPreviewSubtitle">Lampiran Surat Izin</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <a id="btnDownloadBerkas" href="#" download class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;" title="Unduh Berkas">
                        <i class="fa-solid fa-download"></i> Unduh
                    </a>
                    <a id="btnOpenBerkas" href="#" target="_blank" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;" title="Buka di Tab Baru">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>

            {{-- Body --}}
            <div class="modal-body p-0" style="background:#1a1a2e; min-height: 60vh; position:relative;">

                {{-- Loading spinner --}}
                <div id="previewLoading" class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;">
                    <div class="spinner-border" style="color:#7c3aed; width:3rem;height:3rem;" role="status"></div>
                    <p class="text-white mt-3 opacity-75">Memuat berkas…</p>
                </div>

                {{-- Image viewer --}}
                <div id="previewImageWrap" class="d-none" style="overflow:auto; max-height:80vh; display:flex!important; align-items:center; justify-content:center; padding:1.5rem;">
                    <img id="previewImage" src="" alt="Surat Izin / Surat Dokter"
                        style="max-width:100%; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.5); cursor:zoom-in; transition:transform .25s ease;"
                        onclick="this.style.transform = this.style.transform === 'scale(1.8)' ? 'scale(1)' : 'scale(1.8)'; this.style.cursor = this.style.transform === 'scale(1)' ? 'zoom-in' : 'zoom-out';">
                </div>

                {{-- PDF viewer --}}
                <div id="previewPdfWrap" class="d-none" style="height:80vh;">
                    <iframe id="previewPdf" src="" style="width:100%;height:100%;border:none;" title="PDF Viewer"></iframe>
                </div>

                {{-- Unsupported file fallback --}}
                <div id="previewOtherWrap" class="d-none d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;">
                    <i class="fa-solid fa-file-circle-question fa-4x mb-4" style="color:#7c3aed;"></i>
                    <h5 class="text-white fw-bold">Format File Tidak Dapat Ditampilkan</h5>
                    <p class="text-white opacity-75 mb-4">Klik tombol di bawah untuk mengunduh atau membuka file.</p>
                    <a id="btnFallbackOpen" href="#" target="_blank" class="btn btn-primary">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka File
                    </a>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-0" style="background:#111827; padding:.75rem 1.5rem;">
                <span id="modalFileInfo" class="text-muted small me-auto"></span>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const modal     = document.getElementById('modalPreviewBerkas');
    const imgWrap   = document.getElementById('previewImageWrap');
    const pdfWrap   = document.getElementById('previewPdfWrap');
    const otherWrap = document.getElementById('previewOtherWrap');
    const loading   = document.getElementById('previewLoading');
    const img       = document.getElementById('previewImage');
    const pdf       = document.getElementById('previewPdf');
    const subtitle  = document.getElementById('modalPreviewSubtitle');
    const fileInfo  = document.getElementById('modalFileInfo');
    const btnDl     = document.getElementById('btnDownloadBerkas');
    const btnOpen   = document.getElementById('btnOpenBerkas');
    const btnFb     = document.getElementById('btnFallbackOpen');

    function hideAll() {
        [imgWrap, pdfWrap, otherWrap].forEach(el => el.classList.add('d-none'));
        loading.classList.remove('d-none');
        img.style.transform = 'scale(1)';
        img.style.cursor    = 'zoom-in';
        pdf.src = '';
    }

    modal.addEventListener('show.bs.modal', function (e) {
        const btn  = e.relatedTarget;
        const url  = btn.dataset.url;
        const type = btn.dataset.type;   // 'image' | 'pdf' | 'other'
        const nama = btn.dataset.nama;
        const ext  = btn.dataset.ext.toUpperCase();

        hideAll();

        document.getElementById('modalPreviewLabel').textContent = 'Pratinjau Berkas — ' + nama;
        subtitle.textContent  = 'Lampiran Surat Izin / Surat Dokter';
        fileInfo.textContent  = 'Format: ' + ext;
        btnDl.href   = url;
        btnOpen.href = url;
        btnFb.href   = url;

        if (type === 'image') {
            img.onload = function () {
                loading.classList.add('d-none');
                imgWrap.classList.remove('d-none');
            };
            img.onerror = function () {
                loading.classList.add('d-none');
                otherWrap.classList.remove('d-none');
            };
            img.src = url;
        } else if (type === 'pdf') {
            loading.classList.add('d-none');
            pdfWrap.classList.remove('d-none');
            pdf.src = url;
        } else {
            loading.classList.add('d-none');
            otherWrap.classList.remove('d-none');
        }
    });

    modal.addEventListener('hidden.bs.modal', function () {
        pdf.src = '';
        img.src = '';
    });
}());
</script>
@endpush

@endsection