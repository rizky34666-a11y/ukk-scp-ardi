@extends('layouts.app')

@section('title', 'Rekap Seluruh Jurnal Mengajar Guru - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Rekapitulasi Jurnal Mengajar Guru</h1>
        <p class="page-subtitle">Monitoring pelaksanaan pembelajaran, arsip bukti dokumentasi KBM, dan data kehadiran siswa per sesi mengajar</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('rekap.absensi.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-clipboard-user me-1"></i> Rekap Absensi Siswa (Izin & Alfa)
        </a>
        <a href="{{ route('waka.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection

@section('content')

{{-- Statistik Ringkasan Absensi --}}
<div class="row g-3 mb-4">
    @php
        $totalJurnal = count($jurnalList);
        $totalTidakHadir = 0;
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlfa = 0;

        foreach ($jurnalList as $j) {
            foreach ($j->attendances as $att) {
                if ($att->status !== 'Hadir') $totalTidakHadir++;
                if ($att->status === 'Sakit') $totalSakit++;
                if ($att->status === 'Izin') $totalIzin++;
                if ($att->status === 'Alfa') $totalAlfa++;
            }
        }
    @endphp
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon tone-blue">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalJurnal }}</div>
                <div class="stat-label">Total Jurnal</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: var(--accent-green-soft); color: var(--accent-green);">
                <i class="fa-solid fa-briefcase-medical"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalSakit }}</div>
                <div class="stat-label">Total Sakit</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: var(--accent-warning-soft); color: var(--accent-warning);">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalIzin }}</div>
                <div class="stat-label">Total Izin</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: var(--accent-pink-soft); color: var(--accent-pink);">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalAlfa }}</div>
                <div class="stat-label">Total Alfa</div>
            </div>
        </div>
    </div>
</div>

<div class="app-card">
    <div class="app-card-header">
        <h5 class="app-card-title">
            <i class="fa-solid fa-clipboard-list text-primary"></i>
            Data Jurnal KBM Seluruh Guru
        </h5>
        <span class="badge-soft badge-soft-secondary">
            Total Rekap: {{ count($jurnalList) }} Jurnal
        </span>
    </div>
    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table app-table align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Tanggal KBM</th>
                        <th>Nama Guru Pengampu</th>
                        <th>Kelas & Mata Pelajaran</th>
                        <th>Topik / Rangkuman Materi</th>
                        <th class="text-center">Kehadiran</th>
                        <th class="text-center" style="width: 120px;">Foto Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jurnalList as $index => $jurnal)
                        <tr>
                            <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">
                                    {{ $jurnal->date ? \Carbon\Carbon::parse($jurnal->date)->translatedFormat('d M Y') : '-' }}
                                </div>
                                <small class="text-muted">
                                    {{ $jurnal->date ? \Carbon\Carbon::parse($jurnal->date)->translatedFormat('l') : '-' }}
                                </small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">
                                    {{ $jurnal->jadwal?->guru?->nama_guru ?? 'Guru Tidak Ditemukan' }}
                                </div>
                                @if(!empty($jurnal->jadwal?->guru?->nip))
                                    <small class="text-muted font-monospace">NIP: {{ $jurnal->jadwal->guru->nip }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-primary">
                                    {{ $jurnal->jadwal?->kelas?->nama_kelas ?? ($jurnal->jadwal?->id_kelas ? 'Kelas '.$jurnal->jadwal->id_kelas : '-') }}
                                </div>
                                <div class="small text-muted">
                                    {{ $jurnal->jadwal?->mataPelajaran?->nama_mapel ?? ($jurnal->jadwal?->id_mapel ? 'Mapel '.$jurnal->jadwal->id_mapel : '-') }}
                                </div>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @if(!empty($jurnal->jadwal?->label_sesi))
                                        <span class="badge bg-primary text-white" style="font-size: 0.68rem;">
                                            {{ $jurnal->jadwal->label_sesi }}
                                        </span>
                                    @endif
                                    @if(!empty($jurnal->jadwal?->cycle_type))
                                        <span class="badge-soft badge-soft-info" style="font-size: 0.7rem;">
                                            {{ $jurnal->jadwal->cycle_type }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="small text-dark" style="max-width: 320px;">
                                    {{ $jurnal->topic_description ?? '-' }}
                                </div>
                            </td>
                            <td class="text-center">
                                @php
                                    $absenTidakHadir = $jurnal->attendances->where('status', '!=', 'Hadir');
                                @endphp
                                @if($absenTidakHadir->count() > 0)
                                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalAbsensiWaka{{ $jurnal->id }}" title="Lihat detail absensi">
                                        <i class="fa-solid fa-user-xmark"></i>
                                        <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;">{{ $absenTidakHadir->count() }}</span>
                                    </button>
                                @else
                                    <span class="badge bg-success" style="font-size: 0.68rem;" title="Semua siswa hadir / belum ada data absensi">
                                        <i class="fa-solid fa-check"></i> Lengkap
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($jurnal->photo_path)
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm py-1 px-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalFotoBuktiWaka" 
                                            data-foto-url="{{ asset($jurnal->photo_path) }}"
                                            data-guru="{{ $jurnal->jadwal?->guru?->nama_guru ?? 'Guru' }}"
                                            data-mapel="{{ $jurnal->jadwal?->mataPelajaran?->nama_mapel ?? '-' }}"
                                            data-kelas="{{ $jurnal->jadwal?->kelas?->nama_kelas ?? '-' }}"
                                            data-tanggal="{{ $jurnal->date ? \Carbon\Carbon::parse($jurnal->date)->translatedFormat('d F Y') : '-' }}"
                                            data-materi="{{ $jurnal->topic_description ?? '-' }}"
                                            title="Lihat Foto Bukti">
                                        <i class="fa-regular fa-image me-1"></i> Lihat Foto
                                    </button>
                                @else
                                    <span class="badge-soft badge-soft-secondary">Tidak ada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-clipboard fa-2x mb-2 text-secondary d-block"></i>
                                Belum ada data jurnal mengajar yang terekam dari para guru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Detail Absensi untuk Waka — dirender di luar tabel agar backdrop tidak menimpa modal --}}
@foreach($jurnalList as $jurnal)
    @php
        $absenTidakHadirModal = $jurnal->attendances->where('status', '!=', 'Hadir');
    @endphp
    @if($absenTidakHadirModal->count() > 0)
        <div class="modal fade" id="modalAbsensiWaka{{ $jurnal->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="border-radius: var(--radius); overflow: hidden;">
                    <div class="modal-header" style="background: var(--color-dark); color: #fff; padding: 0.85rem 1.25rem;">
                        <h6 class="modal-title mb-0">
                            <i class="fa-solid fa-clipboard-user me-2"></i>
                            Detail Absensi Siswa Tidak Hadir
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="px-3 py-2" style="background: var(--accent-blue-soft); border-bottom: 1px solid var(--accent-blue-border);">
                            <div class="d-flex flex-wrap gap-3">
                                <small class="text-primary fw-semibold">
                                    <i class="fa-solid fa-calendar me-1"></i>
                                    {{ $jurnal->date ? \Carbon\Carbon::parse($jurnal->date)->translatedFormat('l, d M Y') : '-' }}
                                </small>
                                <small class="text-primary fw-semibold">
                                    <i class="fa-solid fa-chalkboard-user me-1"></i>
                                    {{ $jurnal->jadwal?->guru?->nama_guru ?? '-' }}
                                </small>
                                <small class="text-primary fw-semibold">
                                    <i class="fa-solid fa-school me-1"></i>
                                    {{ $jurnal->jadwal?->kelas?->nama_kelas ?? ($jurnal->jadwal?->id_kelas ? 'Kelas '.$jurnal->jadwal->id_kelas : '-') }}
                                    — {{ $jurnal->jadwal?->mataPelajaran?->nama_mapel ?? '-' }}
                                </small>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead style="background: var(--page-bg-soft);">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">No</th>
                                        <th>Nama Siswa</th>
                                        <th>NISN</th>
                                        <th class="text-center">Status</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($absenTidakHadirModal as $att)
                                        <tr>
                                            <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                            <td class="fw-semibold text-dark">{{ $att->siswa?->nama_siswa ?? 'Siswa Tidak Ditemukan' }}</td>
                                            <td><small class="text-muted font-monospace">{{ $att->siswa?->nisn ?? '-' }}</small></td>
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
                                            <td><small class="text-muted">{{ $att->keterangan ?? '-' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-3 py-2" style="background: var(--page-bg-soft); border-top: 1px solid var(--card-border);">
                            <div class="d-flex flex-wrap gap-2">
                                @php
                                    $cSakit = $absenTidakHadirModal->where('status', 'Sakit')->count();
                                    $cIzin = $absenTidakHadirModal->where('status', 'Izin')->count();
                                    $cAlfa = $absenTidakHadirModal->where('status', 'Alfa')->count();
                                @endphp
                                <span class="badge bg-info text-white" style="font-size: 0.7rem;">Sakit: {{ $cSakit }}</span>
                                <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Izin: {{ $cIzin }}</span>
                                <span class="badge bg-danger" style="font-size: 0.7rem;">Alfa: {{ $cAlfa }}</span>
                                <span class="badge bg-dark" style="font-size: 0.7rem;">Total Tidak Hadir: {{ $absenTidakHadirModal->count() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="padding: 0.65rem 1.25rem;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- Modal Pratinjau Foto Bukti KBM (Tidak Berpindah Halaman) --}}
<div class="modal fade" id="modalFotoBuktiWaka" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow border-0" style="border-radius: var(--radius); overflow: hidden;">
            <div class="modal-header py-2 px-3 text-white" style="background: var(--color-dark);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-camera text-primary"></i>
                    <div>
                        <h6 class="modal-title mb-0 fw-bold" style="font-size: 0.92rem;">Bukti Dokumentasi Kegiatan KBM</h6>
                        <small class="text-white-50" id="wakaFotoSubtitle" style="font-size: 0.75rem;">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center bg-dark position-relative d-flex align-items-center justify-content-center" style="min-height: 320px; max-height: 75vh; overflow: hidden;">
                <img id="wakaFotoImg" src="" alt="Bukti Foto KBM" class="img-fluid" style="max-height: 75vh; width: auto; object-fit: contain;">
            </div>
            <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                <div class="text-start pe-3 text-truncate" style="max-width: 65%;">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">Topik Pembelajaran:</small>
                    <span class="text-dark small fw-semibold text-truncate d-inline-block" id="wakaFotoMateri" style="max-width: 100%;">-</span>
                </div>
                <div class="d-flex gap-2">
                    <a id="wakaFotoDownload" href="#" target="_blank" download class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-download me-1"></i> Buka Gambar
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalFoto = document.getElementById('modalFotoBuktiWaka');
    if (modalFoto) {
        modalFoto.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            const fotoUrl = button.getAttribute('data-foto-url');
            const guru = button.getAttribute('data-guru');
            const mapel = button.getAttribute('data-mapel');
            const kelas = button.getAttribute('data-kelas');
            const tanggal = button.getAttribute('data-tanggal');
            const materi = button.getAttribute('data-materi');

            document.getElementById('wakaFotoImg').src = fotoUrl || '';
            document.getElementById('wakaFotoDownload').href = fotoUrl || '#';
            document.getElementById('wakaFotoSubtitle').textContent = (guru || 'Guru') + ' • ' + (kelas || '-') + ' (' + (mapel || '-') + ') • ' + (tanggal || '-');
            document.getElementById('wakaFotoMateri').textContent = materi || '-';
        });
    }
});
</script>
@endpush