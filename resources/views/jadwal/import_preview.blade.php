@extends('layouts.app')

@section('title', 'Pratinjau Impor Jadwal - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Pratinjau Impor Jadwal</h1>
        <p class="page-subtitle">Periksa hasilnya dulu. Belum ada data yang berubah sampai Anda menekan Simpan.</p>
    </div>
    <form action="{{ route('jadwal.import.batal') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-xmark me-1"></i> Batalkan &amp; kembali</button>
    </form>
</div>
@endsection

@section('content')
@php
    $adaError = $h['jumlah_error'] > 0;
    $adaHapus = count($h['hapus_ids']) > 0;
    $adaPeringatan = $h['jumlah_peringatan'] > 0;
    $adaPerubahan = count($h['tambah']) > 0 || $adaHapus;
    $bisaSimpan = !$adaError && $adaPerubahan && !$terkunci;
    $perluKonfirmasi = $adaHapus || $adaPeringatan;
@endphp

<div class="app-card mb-4">
    <div class="app-card-body small">
        <div class="row g-2">
            <div class="col-md-6"><span class="text-muted">File:</span> <strong>{{ $m['nama_file'] }}</strong></div>
            <div class="col-md-3"><span class="text-muted">Tahun ajaran:</span> <strong>{{ $tahunNama }}</strong></div>
            <div class="col-md-3"><span class="text-muted">Putaran:</span> <strong>{{ $m['putaran'] }}</strong></div>
            <div class="col-12"><span class="text-muted">Cara:</span> <strong>{{ $m['mode'] === 'timpa' ? 'Timpa jadwal kelas yang ada di file' : 'Tambah saja' }}</strong></div>
        </div>
    </div>
</div>

@if($terkunci)
    <div class="app-alert app-alert-warning mb-4">
        <i class="fa-solid fa-lock fs-5 text-warning mt-1"></i>
        <div><strong>Jadwal sedang dikunci.</strong> Buka kunci jadwal di halaman Kelola Jadwal lalu muat ulang halaman ini agar dapat disimpan.</div>
    </div>
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['Baris dibaca', $h['total'], 'secondary'],
        ['Akan ditambah', count($h['tambah']), 'success'],
        ['Tetap (identik)', $h['tetap'], 'primary'],
        ['Akan dihapus', count($h['hapus_ids']), $adaHapus ? 'danger' : 'secondary'],
        ['Baris bermasalah', $h['jumlah_error'], $adaError ? 'danger' : 'secondary'],
        ['Peringatan ruangan', $h['jumlah_peringatan'], $adaPeringatan ? 'warning' : 'secondary'],
    ] as [$judul, $nilai, $warna])
        <div class="col-6 col-md-4 col-xl-2">
            <div class="app-card h-100">
                <div class="app-card-body text-center py-3">
                    <div class="fs-3 fw-bold text-{{ $warna }}">{{ number_format($nilai, 0, ',', '.') }}</div>
                    <div class="small text-muted">{{ $judul }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($adaError)
    <div class="app-card mb-4 border-danger">
        <div class="app-card-header">
            <h5 class="app-card-title text-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ number_format($h['jumlah_error'], 0, ',', '.') }} masalah ditemukan &mdash; tidak ada yang disimpan</h5>
        </div>
        <div class="app-card-body p-0">
            <p class="small text-muted px-3 pt-3 mb-2">Perbaiki di Excel lalu unggah ulang dari Kelola Jadwal &gt; Import Jadwal. Nomor baris sesuai nomor baris di Excel.</p>
            <div class="table-responsive" style="max-height: 420px;">
                <table class="table app-table align-middle mb-0">
                    <thead><tr><th style="width:90px;" class="text-center">Baris</th><th>Masalah</th></tr></thead>
                    <tbody>
                        @foreach(array_slice($h['error'], 0, 200) as $e)
                            <tr><td class="text-center fw-semibold">{{ $e['no'] }}</td><td class="small">{{ $e['pesan'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($h['error']) > 200)
                <p class="small text-muted px-3 py-2 mb-0">Menampilkan 200 pertama. Perbaiki lalu unggah ulang untuk melihat sisanya.</p>
            @endif
        </div>
    </div>
@endif

@if(!empty($h['per_kelas']))
    <div class="app-card mb-4">
        <div class="app-card-header"><h5 class="app-card-title"><i class="fa-solid fa-users-rectangle text-primary"></i> Ringkasan per kelas</h5></div>
        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead><tr><th>Kelas</th><th class="text-center">Ditambah</th><th class="text-center">Tetap</th><th class="text-center">Dihapus</th></tr></thead>
                <tbody>
                    @foreach($h['per_kelas'] as $pk)
                        <tr>
                            <td class="fw-semibold">{{ $pk['label'] }}</td>
                            <td class="text-center">{{ $pk['baru'] }}</td>
                            <td class="text-center">{{ $pk['tetap'] }}</td>
                            <td class="text-center {{ $pk['hapus'] > 0 ? 'text-danger fw-bold' : '' }}">{{ $pk['hapus'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($adaPeringatan)
    <div class="app-card mb-4">
        <div class="app-card-header"><h5 class="app-card-title text-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ number_format($h['jumlah_peringatan'], 0, ',', '.') }} peringatan: ruangan dipakai bersamaan</h5></div>
        <div class="app-card-body small">
            <p class="text-muted mb-2">Ini tidak menghalangi penyimpanan (mis. lapangan olahraga atau lab yang dipakai bersama), tetapi periksa apakah memang disengaja.</p>
            <details>
                <summary class="fw-semibold" style="cursor:pointer;">Lihat daftar (maks. 100)</summary>
                <ul class="mt-2 mb-0">
                    @foreach(array_slice($h['peringatan'], 0, 100) as $p)
                        <li>Baris {{ $p['no'] }}: {{ $p['pesan'] }}</li>
                    @endforeach
                </ul>
            </details>
        </div>
    </div>
@endif

@if(count($h['tambah']) > 0)
    <div class="app-card mb-4">
        <div class="app-card-header"><h5 class="app-card-title"><i class="fa-solid fa-calendar-plus text-success"></i> Jadwal yang akan ditambahkan {{ count($h['tambah']) > 100 ? '(100 pertama dari ' . number_format(count($h['tambah']), 0, ',', '.') . ')' : '' }}</h5></div>
        <div class="table-responsive" style="max-height: 480px;">
            <table class="table app-table align-middle mb-0">
                <thead><tr><th class="text-center">Baris</th><th>Kelas</th><th>Hari</th><th class="text-center">Jam ke</th><th>Waktu</th><th>Mapel</th><th>Guru</th><th>Ruangan</th></tr></thead>
                <tbody>
                    @foreach(array_slice($h['tambah'], 0, 100) as $t)
                        <tr class="small">
                            <td class="text-center text-muted">{{ $t['no'] }}</td>
                            <td class="fw-semibold">{{ $t['kelas_label'] }}</td>
                            <td>{{ $t['hari'] }}</td>
                            <td class="text-center">{{ $t['jam_ke_mulai'] }}{{ $t['jam_ke_selesai'] != $t['jam_ke_mulai'] ? '–' . $t['jam_ke_selesai'] : '' }}</td>
                            <td>{{ substr($t['jam_mulai'], 0, 5) }}–{{ substr($t['jam_selesai'], 0, 5) }}</td>
                            <td>{{ $t['mapel_nama'] }} <code class="ms-1">{{ $t['id_mapel'] }}</code></td>
                            <td>{{ $t['guru_nama'] }}</td>
                            <td>{{ $t['ruangan_nama'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if(!$adaError && !$adaPerubahan)
    <div class="app-alert app-alert-info mb-4">
        <i class="fa-solid fa-circle-info fs-5 mt-1"></i>
        <div><strong>Tidak ada perubahan.</strong> Isi file sama persis dengan jadwal yang sudah ada.</div>
    </div>
@endif

@if($bisaSimpan)
    <div class="app-card mb-5">
        <div class="app-card-body">
            <form action="{{ route('jadwal.import.simpan') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $m['token'] }}">
                @if($adaHapus)
                    <div class="alert alert-danger small">
                        <strong>{{ number_format(count($h['hapus_ids']), 0, ',', '.') }} jadwal lama akan dihapus permanen</strong>
                        @if($h['hapus_jurnal'] > 0)
                            beserta <strong>{{ number_format($h['hapus_jurnal'], 0, ',', '.') }} jurnal mengajar</strong> (dan kehadiran siswa di dalamnya)
                        @endif
                        , termasuk pengajuan tukar jadwal yang terkait. Sebaiknya backup database dulu.
                    </div>
                @endif
                @if($perluKonfirmasi)
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="konfirmasi" value="1" id="konfirmasi" required>
                        <label class="form-check-label small" for="konfirmasi">
                            Saya sudah memeriksa pratinjau{{ $adaHapus ? ', mengerti bahwa jadwal lama dihapus permanen' : '' }}{{ $adaPeringatan ? ' dan menerima peringatan ruangan' : '' }}.
                        </label>
                    </div>
                @endif
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Jadwal</button>
            </form>
        </div>
    </div>
@elseif($adaError)
    <p class="small text-muted mb-5">Tombol Simpan muncul setelah semua baris benar.</p>
@endif
@endsection
