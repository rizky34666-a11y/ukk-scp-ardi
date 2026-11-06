@extends('layouts.app')

@section('title', 'Pengajuan Izin Siswa - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Pengajuan Izin / Sakit Siswa</h1>
        <p class="page-subtitle">Ajukan permohonan izin ketidakhadiran sekolah dan pantau status persetujuan dari Wali Kelas atau BK</p>
    </div>
    <div>
        <span class="badge-soft badge-soft-primary px-3 py-2">
            <i class="fa-solid fa-user-graduate me-1"></i>
            Portal Siswa
        </span>
    </div>
</div>
@endsection

@section('content')

@php
    $latestIzin = $riwayatIzin->first();
@endphp

{{-- Banner Status Permohonan Terakhir untuk UX Siswa --}}
@if($latestIzin)
    @if($latestIzin->status_approval === 'Pending')
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4 rounded-3" role="alert">
            <div class="fs-3 text-warning">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold mb-1">Status Permohonan Terakhir: Sedang Menunggu Verifikasi</div>
                <div class="small text-dark opacity-75">
                    Surat izin Anda untuk periode <strong>{{ \Carbon\Carbon::parse($latestIzin->start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($latestIzin->end_date)->translatedFormat('d M Y') }}</strong> sedang dalam antrean pemeriksaan oleh Wali Kelas atau Guru BK.
                </div>
            </div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold">Menunggu</span>
        </div>
    @elseif($latestIzin->status_approval === 'Approved')
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4 rounded-3" role="alert">
            <div class="fs-3 text-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold mb-1">Status Permohonan Terakhir: Telah Disetujui!</div>
                <div class="small text-dark opacity-75">
                    Surat permohonan izin Anda periode <strong>{{ \Carbon\Carbon::parse($latestIzin->start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($latestIzin->end_date)->translatedFormat('d M Y') }}</strong> telah disetujui. Presensi Anda pada kegiatan belajar mengajar otomatis tercatat resmi.
                </div>
            </div>
            <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-semibold">Disetujui</span>
        </div>
    @elseif($latestIzin->status_approval === 'Rejected')
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4 rounded-3" role="alert">
            <div class="fs-3 text-danger">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold mb-1">Status Permohonan Terakhir: Ditolak</div>
                <div class="small text-dark opacity-75">
                    Surat izin Anda periode <strong>{{ \Carbon\Carbon::parse($latestIzin->start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($latestIzin->end_date)->translatedFormat('d M Y') }}</strong> tidak disetujui. Silakan hubungi Wali Kelas atau Guru BK jika ada pertanyaan.
                </div>
            </div>
            <span class="badge bg-danger text-white px-3 py-2 rounded-pill fw-semibold">Ditolak</span>
        </div>
    @endif
@endif

<div class="row g-4">
    <!-- Form Pengajuan Izin -->
    <div class="col-lg-4">
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title">
                    <i class="fa-solid fa-file-circle-plus text-primary"></i>
                    Form Surat Permohonan
                </h5>
            </div>
            <div class="app-card-body">
                <form action="{{ route('siswa.izin.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    @php
                        $today = date('Y-m-d');
                    @endphp

                    <div class="mb-3">
                        <label class="form-label" for="start_date">Tanggal Mulai Izin <span class="text-danger">*</span></label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date', $today) }}" min="{{ $today }}" required>
                        <div class="form-text">Tanggal izin minimal hari ini ({{ \Carbon\Carbon::parse($today)->translatedFormat('d M Y') }}).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="end_date">Tanggal Selesai Izin <span class="text-danger">*</span></label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="{{ old('end_date', $today) }}" min="{{ $today }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="reason">Alasan Permohonan Izin <span class="text-danger">*</span></label>
                        <textarea id="reason" name="reason" class="form-control" rows="3" required placeholder="Contoh: Sakit demam berdarah / Mengikuti upacara adat keluarga...">{{ old('reason') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="document">Lampiran Bukti / Surat Keterangan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-paperclip"></i></span>
                            <input type="file" id="document" name="document" class="form-control" accept=".pdf,image/*" required>
                        </div>
                        <div class="form-text">Surat dokter atau surat permohonan orang tua. Format: PDF/JPG/PNG.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Surat Pengajuan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat Pengajuan Izin -->
    <div class="col-lg-8">
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title">
                    <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                    Riwayat Pengajuan Izin Saya
                </h5>
                <span class="badge-soft badge-soft-secondary">
                    Total: {{ count($riwayatIzin) }} Pengajuan
                </span>
            </div>
            <div class="app-card-body p-0">
                <div class="table-responsive">
                    <table class="table app-table align-middle">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 45px;">No</th>
                                <th>Periode Tanggal</th>
                                <th>Alasan Izin</th>
                                <th class="text-center">Dokumen</th>
                                <th class="text-center">Status Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayatIzin as $index => $izin)
                                <tr>
                                    <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            {{ $izin->start_date ? \Carbon\Carbon::parse($izin->start_date)->translatedFormat('d M Y') : '-' }}
                                        </div>
                                        <small class="text-muted">
                                            s/d {{ $izin->end_date ? \Carbon\Carbon::parse($izin->end_date)->translatedFormat('d M Y') : '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="small text-dark" style="max-width: 250px;">
                                            {{ $izin->reason ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($izin->document_path)
                                            @php
                                                $ext = strtolower(pathinfo($izin->document_path, PATHINFO_EXTENSION));
                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                $isPdf   = $ext === 'pdf';
                                            @endphp
                                            <button type="button"
                                                class="btn btn-outline-primary btn-sm btn-preview-berkas"
                                                title="Pratinjau Berkas Lampiran"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalPreviewBerkasSiswa"
                                                data-url="{{ asset($izin->document_path) }}"
                                                data-type="{{ $isImage ? 'image' : ($isPdf ? 'pdf' : 'other') }}"
                                                data-nama="Izin {{ $izin->start_date ? \Carbon\Carbon::parse($izin->start_date)->translatedFormat('d M Y') : '-' }}"
                                                data-ext="{{ $ext }}">
                                                <i class="fa-solid fa-{{ $isImage ? 'image' : ($isPdf ? 'file-pdf' : 'file-arrow-down') }} me-1"></i>Lihat
                                            </button>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($izin->status_approval == 'Approved')
                                            <span class="badge-soft badge-soft-success">
                                                <i class="fa-solid fa-check"></i> Disetujui
                                            </span>
                                        @elseif($izin->status_approval == 'Rejected')
                                            <span class="badge-soft badge-soft-danger">
                                                <i class="fa-solid fa-xmark"></i> Ditolak
                                            </span>
                                        @else
                                            <span class="badge-soft badge-soft-warning">
                                                <i class="fa-regular fa-clock"></i> Menunggu Verifikasi
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-folder-open fa-2x mb-2 text-secondary d-block"></i>
                                        Anda belum pernah membuat pengajuan izin.
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
{{-- ===================== MODAL PREVIEW BERKAS SISWA ===================== --}}
<div class="modal fade" id="modalPreviewBerkasSiswa" tabindex="-1" aria-labelledby="modalPreviewSiswaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">

            {{-- Header --}}
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 1rem 1.5rem;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-file-magnifying-glass text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 text-white fw-bold" id="modalPreviewSiswaLabel">Pratinjau Berkas</h5>
                        <small class="text-white opacity-75" id="modalPreviewSiswaSubtitle">Lampiran Surat Izin Saya</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <a id="btnDownloadBerkasSiswa" href="#" download class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;" title="Unduh Berkas">
                        <i class="fa-solid fa-download"></i> Unduh
                    </a>
                    <a id="btnOpenBerkasSiswa" href="#" target="_blank" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;" title="Buka di Tab Baru">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>

            {{-- Body --}}
            <div class="modal-body p-0" style="background:#1a1a2e; min-height: 60vh; position:relative;">

                <div id="previewLoadingSiswa" class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;">
                    <div class="spinner-border" style="color:#7c3aed; width:3rem;height:3rem;" role="status"></div>
                    <p class="text-white mt-3 opacity-75">Memuat berkas…</p>
                </div>

                <div id="previewImageWrapSiswa" class="d-none" style="overflow:auto; max-height:80vh; display:flex!important; align-items:center; justify-content:center; padding:1.5rem;">
                    <img id="previewImageSiswa" src="" alt="Lampiran Surat Izin"
                        style="max-width:100%; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.5); cursor:zoom-in; transition:transform .25s ease;"
                        onclick="this.style.transform = this.style.transform === 'scale(1.8)' ? 'scale(1)' : 'scale(1.8)'; this.style.cursor = this.style.transform === 'scale(1)' ? 'zoom-in' : 'zoom-out';">
                </div>

                <div id="previewPdfWrapSiswa" class="d-none" style="height:80vh;">
                    <iframe id="previewPdfSiswa" src="" style="width:100%;height:100%;border:none;" title="PDF Viewer"></iframe>
                </div>

                <div id="previewOtherWrapSiswa" class="d-none d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;">
                    <i class="fa-solid fa-file-circle-question fa-4x mb-4" style="color:#7c3aed;"></i>
                    <h5 class="text-white fw-bold">Format File Tidak Dapat Ditampilkan</h5>
                    <p class="text-white opacity-75 mb-4">Klik tombol di bawah untuk membuka file.</p>
                    <a id="btnFallbackOpenSiswa" href="#" target="_blank" class="btn btn-primary">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka File
                    </a>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-0" style="background:#111827; padding:.75rem 1.5rem;">
                <span id="modalFileInfoSiswa" class="text-muted small me-auto"></span>
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

    const modal     = document.getElementById('modalPreviewBerkasSiswa');
    const imgWrap   = document.getElementById('previewImageWrapSiswa');
    const pdfWrap   = document.getElementById('previewPdfWrapSiswa');
    const otherWrap = document.getElementById('previewOtherWrapSiswa');
    const loading   = document.getElementById('previewLoadingSiswa');
    const img       = document.getElementById('previewImageSiswa');
    const pdf       = document.getElementById('previewPdfSiswa');
    const subtitle  = document.getElementById('modalPreviewSiswaSubtitle');
    const fileInfo  = document.getElementById('modalFileInfoSiswa');
    const btnDl     = document.getElementById('btnDownloadBerkasSiswa');
    const btnOpen   = document.getElementById('btnOpenBerkasSiswa');
    const btnFb     = document.getElementById('btnFallbackOpenSiswa');

    // Sinkronisasi batasan tanggal: tanggal selesai tidak boleh kurang dari tanggal mulai
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function () {
            if (this.value) {
                endDateInput.min = this.value;
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            }
        });
    }

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
        const type = btn.dataset.type;
        const nama = btn.dataset.nama;
        const ext  = btn.dataset.ext.toUpperCase();

        hideAll();

        document.getElementById('modalPreviewSiswaLabel').textContent = 'Pratinjau Berkas — ' + nama;
        subtitle.textContent = 'Lampiran Surat Izin Saya';
        fileInfo.textContent = 'Format: ' + ext;
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

    // Sinkronisasi otomatis tanggal selesai agar tidak mendahului tanggal mulai
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            if (this.value) {
                endDateInput.min = this.value;
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            }
        });
    }
}());
</script>
@endpush

@endsection