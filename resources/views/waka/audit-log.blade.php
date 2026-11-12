@extends('layouts.app')

@section('title', 'Log Aktivitas - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Log Aktivitas</h1>
        <p class="page-subtitle">Catatan siapa mengubah jadwal, jurnal, presensi, izin, dan akun, beserta waktunya. Hanya-baca: catatan tidak dapat diubah atau dihapus.</p>
    </div>
    <span class="badge-soft badge-soft-primary px-3 py-2">
        <i class="fa-solid fa-shield-halved me-1"></i> {{ $logs->total() }} catatan
    </span>
</div>
@endsection

@section('content')
@php
    $namaModul = ['auth' => 'Akun & Login', 'jadwal' => 'Jadwal', 'jurnal' => 'Jurnal', 'presensi' => 'Presensi', 'izin' => 'Izin Siswa', 'izin_guru' => 'Izin Guru', 'tukar_jadwal' => 'Tukar Jadwal', 'master_siswa' => 'Master: Siswa', 'master_mapel' => 'Master: Mata Pelajaran', 'master_tahun_ajaran' => 'Master: Tahun Ajaran', 'master_ruangan' => 'Master: Ruangan', 'master_kelas' => 'Master: Kelas', 'master_guru' => 'Master: Guru', 'akun' => 'Manajemen Akun', 'konfigurasi' => 'Konfigurasi'];
    $namaAksi = [
        'login' => 'Login', 'login_gagal' => 'Login gagal', 'login_terkunci' => 'Login terkunci', 'logout' => 'Logout',
        'tambah' => 'Tambah', 'ubah' => 'Ubah', 'hapus' => 'Hapus', 'hapus_massal' => 'Hapus massal', 'impor' => 'Impor',
        'verifikasi' => 'Verifikasi', 'catat_presensi' => 'Catat presensi', 'ubah_presensi' => 'Ubah presensi',
        'ganti_password' => 'Ganti password', 'registrasi' => 'Registrasi',
    ];
    $warnaAksi = function ($a) {
        if (in_array($a, ['hapus', 'hapus_massal', 'login_gagal', 'login_terkunci'])) return 'danger';
        if (in_array($a, ['ubah', 'ubah_presensi', 'verifikasi', 'ganti_password'])) return 'warning';
        if (in_array($a, ['tambah', 'catat_presensi', 'impor', 'registrasi'])) return 'success';
        return 'info';
    };
@endphp

<div class="app-card">
    <div class="app-card-body border-bottom">
        <form method="GET" action="{{ route('waka.audit-log') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted mb-1" for="f_q">Cari</label>
                <input type="search" id="f_q" name="q" value="{{ $filter['q'] }}" class="form-control form-control-sm" placeholder="Nama, username, deskripsi, IP...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1" for="f_modul">Modul</label>
                <select id="f_modul" name="modul" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($modulList as $m)
                        <option value="{{ $m }}" {{ $filter['modul'] === $m ? 'selected' : '' }}>{{ $namaModul[$m] ?? ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1" for="f_aksi">Aksi</label>
                <select id="f_aksi" name="aksi" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($aksiList as $a)
                        <option value="{{ $a }}" {{ $filter['aksi'] === $a ? 'selected' : '' }}>{{ $namaAksi[$a] ?? $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1" for="f_dari">Dari tanggal</label>
                <input type="date" id="f_dari" name="dari" value="{{ $filter['dari'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1" for="f_sampai">Sampai tanggal</label>
                <input type="date" id="f_sampai" name="sampai" value="{{ $filter['sampai'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Terapkan</button>
                <a href="{{ route('waka.audit-log') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Keterangan</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">
                            <div class="fw-semibold">{{ $log->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</div>
                            <small class="text-muted">{{ $log->created_at?->timezone(config('app.timezone'))->format('H:i:s') }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $log->nama_lengkap ?? '-' }}</div>
                            <small class="text-muted">{{ $log->username ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="badge-soft badge-soft-{{ $warnaAksi($log->aksi) }}">{{ $namaAksi[$log->aksi] ?? $log->aksi }}</span>
                            <div><small class="text-muted">{{ $namaModul[$log->modul] ?? ucfirst($log->modul) }}{{ $log->target_id ? ' #' . $log->target_id : '' }}</small></div>
                        </td>
                        <td>
                            <div>{{ $log->deskripsi }}</div>
                            @if($log->data_lama || $log->data_baru)
                                <details class="mt-1">
                                    <summary class="small text-primary" style="cursor:pointer;">Lihat rincian data</summary>
                                    @if($log->data_lama)
                                        <div class="small fw-semibold mt-1">Sebelum</div>
                                        <pre class="small bg-light border rounded p-2 mb-1" style="white-space:pre-wrap;word-break:break-word;">{{ json_encode($log->data_lama, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}</pre>
                                    @endif
                                    @if($log->data_baru)
                                        <div class="small fw-semibold mt-1">Sesudah</div>
                                        <pre class="small bg-light border rounded p-2 mb-0" style="white-space:pre-wrap;word-break:break-word;">{{ json_encode($log->data_baru, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}</pre>
                                    @endif
                                </details>
                            @endif
                        </td>
                        <td class="text-nowrap"><small class="text-muted">{{ $log->ip_address ?? '-' }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open fa-2x mb-2 d-block"></i>
                            Belum ada catatan yang cocok.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 p-3 border-top">
                <small class="text-muted">Menampilkan {{ $logs->firstItem() }}&ndash;{{ $logs->lastItem() }} dari {{ $logs->total() }}</small>
                {{ $logs->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
