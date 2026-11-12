@extends('layouts.app')

@section('title', 'Jadwal Pelajaran (Tabel) - UKK SCP')

@push('styles')
<style>
    .tj-sheet { background: #fff; border: 1px solid #d9dee7; border-radius: 4px; padding: 1rem 1rem 0.75rem; }
    .tj-head { text-align: center; margin-bottom: .6rem; }
    .tj-sekolah { font-weight: 700; letter-spacing: .03em; color: #1f3a68; font-size: .95rem; }
    .tj-judul { font-weight: 700; font-size: 1.25rem; color: #172d52; line-height: 1.25; }
    .tj-sub { color: #5b6677; font-size: .82rem; margin-top: .15rem; }
    .tj-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .tj-table { width: 100%; min-width: 820px; border-collapse: separate; border-spacing: 0; table-layout: fixed; font-size: .8rem; }
    .tj-table th, .tj-table td { border-right: 1px solid #c9d1dd; border-bottom: 1px solid #c9d1dd; padding: .35rem .4rem; vertical-align: middle; text-align: center; background: #fff; }
    .tj-table tr > :first-child { border-left: 1px solid #c9d1dd; }
    .tj-table thead tr:first-child > * { border-top: 1px solid #c9d1dd; }
    .tj-table thead th { background: #1f3a68; color: #fff; font-weight: 600; font-size: .82rem; padding: .5rem .4rem; }
    .tj-table thead th.tj-today { background: #b8892b; }
    .tj-table .tj-jam { width: 58px; background: #eef1f7; color: #1f3a68; font-weight: 700; font-size: .85rem; position: sticky; left: 0; z-index: 2; }
    .tj-table thead .tj-jam { background: #172d52; color: #fff; }
    .tj-table td.tj-today { background: #fffaf0; }
    .tj-table td.tj-isi { height: 62px; }
    .tj-table td.tj-kosong { background: #f7f8fa; color: #b4bcc9; }
    .tj-table td.tj-libur { background: repeating-linear-gradient(135deg, #f3f4f7, #f3f4f7 6px, #eceef2 6px, #eceef2 12px); }
    .tj-table td.tj-bentrok { background: #fff4f2; outline: 2px solid #d9534f; outline-offset: -2px; }
    .tj-item + .tj-item { margin-top: .5rem; padding-top: .5rem; border-top: 1px dashed #c9d1dd; }
    .tj-mapel { font-weight: 600; color: #172d52; line-height: 1.25; }
    .tj-guru { color: #3b4658; line-height: 1.25; }
    .tj-kelas { font-weight: 700; color: #1f3a68; }
    .tj-meta { margin-top: .2rem; font-size: .72rem; color: #6b7686; display: flex; gap: .6rem; justify-content: center; flex-wrap: wrap; }
    .tj-foot { color: #6b7686; font-size: .72rem; margin-top: .5rem; text-align: center; }
    .tj-catatan { font-size: .78rem; color: #5b6677; margin-top: .6rem; }
    .tj-toolbar .btn-seg a { text-decoration: none; }
    @media (max-width: 767.98px) {
        .app-content { padding-left: 1rem; padding-right: 1rem; }
        .tj-sheet { padding: .7rem .5rem; }
        .tj-judul { font-size: 1.05rem; }
    }
    @media print {
        @page { size: A4 landscape; margin: 8mm; }
        .app-sidebar, .app-topbar, .app-footer, .sidebar-overlay, .no-print, .app-alert, #appSidebar, .official-crumb, .modal, .modal-backdrop { display: none !important; }
        .tj-table th, .tj-table td { border-right: 1px solid #7d8899 !important; border-bottom: 1px solid #7d8899 !important; }
        .tj-table tr > :first-child { border-left: 1px solid #7d8899 !important; }
        .tj-table thead tr:first-child > * { border-top: 1px solid #7d8899 !important; }
        .app-wrapper, .app-main, .app-content { display: block !important; margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; background: #fff !important; }
        .tj-sheet { border: 0; padding: 0; }
        .tj-scroll { overflow: visible; }
        .tj-table { min-width: 0; font-size: 8.5pt; }
        .tj-table .tj-jam { position: static; }
        .tj-table td.tj-isi { height: 14mm; }
        .tj-table thead th, .tj-table .tj-jam, .tj-table td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .tj-table thead th.tj-today { background: #1f3a68; }
        .tj-table td.tj-today { background: #fff; }
        .tj-judul { font-size: 14pt; }
    }
</style>
@endpush

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 no-print">
    <div>
        <h1 class="page-title">Jadwal Pelajaran</h1>
        <p class="page-subtitle">
            @if($role === 5) Jadwal kelas Anda dalam bentuk tabel, seperti jadwal cetak sekolah.
            @else Tampilan tabel jam ke- per hari, seperti jadwal cetak sekolah. Dapat dicetak atau diunduh ke Excel.
            @endif
        </p>
    </div>
</div>
@endsection

@section('content')
@php
    $qs = fn (array $ubah = []) => array_filter(array_merge(['mode' => $mode, 'putaran' => $putaran, 'kelas' => $mode === 'kelas' ? $kunci : null, 'guru' => $mode === 'guru' ? $kunci : null], $ubah), fn ($v) => $v !== null && $v !== '');
@endphp

<div class="app-card mb-3 no-print tj-toolbar">
    <div class="px-3 py-3 d-flex flex-column gap-2">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if($bolehPilih)
                    <div class="btn-group btn-group-sm btn-seg" role="group" aria-label="Jenis jadwal">
                        <a class="btn {{ $mode === 'kelas' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('tabel-jadwal.index', ['mode' => 'kelas', 'putaran' => $putaran]) }}"><i class="fa-solid fa-users-rectangle me-1"></i> Per Kelas</a>
                        <a class="btn {{ $mode === 'guru' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('tabel-jadwal.index', ['mode' => 'guru', 'putaran' => $putaran]) }}"><i class="fa-solid fa-chalkboard-user me-1"></i> Per Guru</a>
                    </div>
                @endif
                <div class="btn-group btn-group-sm btn-seg" role="group" aria-label="Putaran">
                    @foreach(['Putaran 1', 'Putaran 2'] as $p)
                        <a class="btn {{ $putaran === $p ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('tabel-jadwal.index', $qs(['putaran' => $p])) }}">{{ $p }}</a>
                    @endforeach
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($m)
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Cetak / PDF</button>
                    <a class="btn btn-success btn-sm" href="{{ route('tabel-jadwal.unduh', $qs()) }}"><i class="fa-solid fa-file-excel me-1"></i> Unduh Excel</a>
                @endif
                @if($role === 4 && $m)
                    <a class="btn btn-outline-success btn-sm" href="{{ route('tabel-jadwal.unduh-semua', ['putaran' => $putaran]) }}" title="Satu file Excel, satu sheet per kelas"><i class="fa-solid fa-file-zipper me-1"></i> Semua Kelas</a>
                @endif
            </div>
        </div>

        @if($bolehPilih)
            <form method="GET" action="{{ route('tabel-jadwal.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <input type="hidden" name="putaran" value="{{ $putaran }}">
                <div class="col-12 col-md-6 col-lg-4">
                    @if($mode === 'kelas')
                        <select name="kelas" class="form-select form-select-sm" aria-label="Pilih kelas" onchange="this.form.submit()">
                            @foreach($kelas as $idK => $namaK)
                                <option value="{{ $idK }}" {{ (string) $kunci === (string) $idK ? 'selected' : '' }}>{{ $namaK }}{{ $kelasSaya !== null && (string) $kelasSaya === (string) $idK ? ' (kelas saya)' : '' }}</option>
                            @endforeach
                        </select>
                    @else
                        <select name="guru" class="form-select form-select-sm" aria-label="Pilih guru" onchange="this.form.submit()">
                            @foreach($guru as $idG => $namaG)
                                <option value="{{ $idG }}" {{ (string) $kunci === (string) $idG ? 'selected' : '' }}>{{ $namaG }}{{ $guruSaya !== null && (int) $guruSaya === (int) $idG ? ' (saya)' : '' }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <noscript><div class="col-auto"><button class="btn btn-primary btn-sm">Tampilkan</button></div></noscript>
            </form>
        @endif
    </div>
</div>

@if(!$m)
    <div class="app-card">
        <div class="p-4 text-center text-muted">
            <i class="fa-regular fa-calendar-xmark fa-2x mb-2 d-block"></i>
            @if($role === 5)
                Kelas Anda belum terdaftar atau belum memiliki jadwal pada {{ $putaran }}. Hubungi wali kelas atau Waka Kurikulum.
            @else
                Belum ada jadwal pada {{ $putaran }}.
            @endif
        </div>
    </div>
@else
    <div class="tj-sheet">
        <div class="tj-head">
            <div class="tj-sekolah">SMK NEGERI 1 BANTUL</div>
            <div class="tj-judul">{{ $m['judul'] }}</div>
            <div class="tj-sub">
                {{ $mode === 'guru' ? 'Jadwal mengajar' : 'Jadwal kelas' }} · {{ $putaran }}@if($tahunNama) · {{ $tahunNama }}@endif
                @if($mode === 'guru') · <strong>{{ $m['jp'] }}</strong> jam pelajaran per minggu @endif
            </div>
        </div>

        <div class="tj-scroll">
            <table class="tj-table">
                <thead>
                    <tr>
                        <th class="tj-jam" scope="col">Jam ke</th>
                        @foreach($m['hari'] as $h)
                            <th scope="col" class="{{ $h === $hariIni ? 'tj-today' : '' }}">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for($b = 1; $b <= $m['baris']; $b++)
                        <tr>
                            <th class="tj-jam" scope="row">{{ $b }}</th>
                            @foreach($m['hari'] as $h)
                                @php $k = $m['grid'][$h][$b]; $kelasHariIni = $h === $hariIni ? 'tj-today' : ''; @endphp
                                @if($k['tipe'] === 'mulai')
                                    <td rowspan="{{ $k['rowspan'] }}" class="tj-isi {{ $kelasHariIni }} {{ $k['bentrok'] ? 'tj-bentrok' : '' }}">
                                        @foreach($k['items'] as $it)
                                            <div class="tj-item">
                                                @if($mode === 'guru')
                                                    <div class="tj-kelas">{{ $it['kelas'] }}</div>
                                                    <div class="tj-mapel">{{ $it['mapel'] }}</div>
                                                @else
                                                    <div class="tj-mapel">{{ $it['mapel'] }}</div>
                                                    @if($it['guru'])<div class="tj-guru">{{ $it['guru'] }}</div>@endif
                                                @endif
                                                <div class="tj-meta">
                                                    @if($it['ruangan'])<span><i class="fa-solid fa-door-open"></i> {{ $it['ruangan'] }}</span>@endif
                                                    <span><i class="fa-regular fa-clock"></i> {{ $it['mulai'] }}–{{ $it['selesai'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($k['bentrok'])<div class="tj-meta text-danger fw-semibold"><i class="fa-solid fa-triangle-exclamation"></i> Jadwal bertumpuk</div>@endif
                                    </td>
                                @elseif($k['tipe'] === 'kosong')
                                    <td class="tj-kosong {{ $kelasHariIni }}">-</td>
                                @elseif($k['tipe'] === 'libur')
                                    <td class="tj-libur"></td>
                                @endif
                            @endforeach
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        @if(!empty($m['kegiatan']))
            <div class="tj-catatan">
                Sebelum jam ke-1:
                @foreach($m['kegiatan'] as $hr => $kg){{ $loop->first ? '' : ';' }} {{ $hr }} {{ $kg['mulai'] }}–{{ $kg['selesai'] }} {{ $kg['nama'] }}@endforeach.
            </div>
        @endif

        @if(!empty($m['lainnya']))
            <div class="tj-catatan text-danger no-print">
                <i class="fa-solid fa-triangle-exclamation"></i> {{ count($m['lainnya']) }} jadwal tidak dapat ditampilkan di tabel karena hari atau jamnya di luar struktur jam pelajaran. Periksa di Kelola Jadwal.
            </div>
        @endif

        <div class="tj-foot">Jadwal dihasilkan oleh UKK SCP pada {{ $dihasilkan }}</div>
    </div>

    <details class="mt-3 no-print">
        <summary class="small fw-semibold text-muted" style="cursor:pointer;">Lihat jam pelajaran per hari</summary>
        <div class="table-responsive mt-2">
            <table class="table table-sm table-bordered small bg-white text-center mb-0" style="max-width: 760px;">
                <thead class="table-light">
                    <tr><th>Jam ke</th>@foreach($m['hari'] as $h)<th>{{ $h }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @for($b = 1; $b <= $m['baris']; $b++)
                        <tr>
                            <th class="table-light">{{ $b }}</th>
                            @foreach($m['hari'] as $h)
                                <td>{{ isset($m['sesi'][$h][$b]) ? $m['sesi'][$h][$b]['mulai'] . '–' . $m['sesi'][$h][$b]['selesai'] : '' }}</td>
                            @endforeach
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </details>
@endif
@endsection
