@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Hak Akses - UKK SCP')

@section('page-header')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h1 class="page-title">Manajemen Akun & Hak Akses</h1>
        <p class="page-subtitle">Pusat registrasi pengguna, pemilahan role, serta pengelolaan data akun sistem (Waka, Guru, Siswa)</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="dropdown">
            <button class="btn btn-outline-success btn-sm dropdown-toggle d-flex align-items-center gap-1 shadow-xs" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-download"></i> Unduh Format Excel
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <a class="dropdown-item py-2" href="{{ route('akun.template.siswa') }}">
                        <i class="fa-solid fa-file-excel text-success me-2"></i>
                        <strong>Format Daftar Hadir Siswa</strong>
                        <div class="small text-muted">Sesuai format absensi kelas (NIS & Nama Siswa)</div>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2" href="{{ route('akun.template') }}">
                        <i class="fa-solid fa-users text-primary me-2"></i>
                        <strong>Format Akun Umum</strong>
                        <div class="small text-muted">Semua role (Guru, Wali, BK, Waka, Siswa)</div>
                    </a>
                </li>
            </ul>
        </div>
        <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-xs" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
            <i class="fa-solid fa-file-excel"></i> Import File Excel / CSV
        </button>
        <a href="{{ route('waka.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row g-4">
    <!-- Form Buat Akun Baru & Import Massal -->
    <div class="col-12 col-xl-3">
        <!-- Form Buat Akun Manual -->
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title">
                    <i class="fa-solid fa-user-plus text-primary"></i>
                    Buat Akun Manual
                </h5>
            </div>
            <div class="app-card-body">
                <form action="{{ route('akun.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label" for="username">Username / NIP / NISN <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-id-badge"></i></span>
                            <input type="text" id="username" name="username" class="form-control" value="{{ old('username') }}" required placeholder="Contoh: 198001012005011001">
                        </div>
                        <div class="form-text">Gunakan NIP resmi untuk Guru / Wali Kelas / BK, atau NISN untuk Siswa.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="nama_lengkap">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" required placeholder="Contoh: Budi Santoso, S.Pd.">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email (untuk OTP)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Contoh: nama@email.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Kata Sandi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="Minimal 5 karakter">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="id_role">Pilih Role / Hak Akses <span class="text-danger">*</span></label>
                        <select id="id_role" name="id_role" class="form-select" required>
                            <option value="">-- Pilih Hak Akses --</option>
                            <option value="4" {{ old('id_role') == 4 ? 'selected' : '' }}>Waka Kurikulum / Administrator</option>
                            <option value="2" {{ old('id_role') == 2 ? 'selected' : '' }}>Guru Pengampu Mata Pelajaran</option>
                            <option value="3" {{ old('id_role') == 3 ? 'selected' : '' }}>Wali Kelas (Verifikasi Izin)</option>
                            <option value="7" {{ old('id_role') == 7 ? 'selected' : '' }}>Guru BK (Verifikasi Izin Siswa)</option>
                            <option value="5" {{ old('id_role') == 5 ? 'selected' : '' }}>Siswa</option>
                        </select>
                        <div class="form-text">Role Guru & Wali Kelas otomatis mendaftarkan profil ke tabel guru sekolah.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Akun Baru
                    </button>
                </form>
            </div>
        </div>

        <!-- Card Import Massal Excel -->
        <div class="app-card mt-4 border border-success-subtle bg-light">
            <div class="app-card-body p-3 text-center">
                <div class="mb-2 text-success fs-3">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <h6 class="fw-bold mb-1 text-dark">Import Data dari Excel / CSV</h6>
                <p class="text-muted small mb-3">
                    Bisa langsung upload file <strong>Daftar Hadir Siswa per kelas</strong> (ada kolom NIS & Nama Siswa) atau file akun pengguna umum.
                </p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success btn-sm fw-semibold shadow-xs" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
                        <i class="fa-solid fa-upload me-1"></i> Buka Form Import Excel
                    </button>
                    <a href="{{ route('akun.template.siswa') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-download me-1"></i> Unduh Format Daftar Hadir Siswa
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Pengguna Terbagi Berdasarkan Role -->
    <div class="col-12 col-xl-9">
        @php
            $filterAktif = ($q ?? '') !== '' || ($roleFilter ?? '') !== '' || ($kelasFilter ?? '') !== '';
            $tabRole = [
                ''  => ['Semua', 'fa-users', 'text-primary', null],
                '2' => ['Guru Pengampu', 'fa-chalkboard-user', 'text-primary', 2],
                '3' => ['Wali Kelas', 'fa-user-tie', 'text-success', 3],
                '7' => ['Guru BK', 'fa-user-doctor', 'text-info', 7],
                '4' => ['Waka Kurikulum', 'fa-user-shield', 'text-danger', 4],
                '5' => ['Siswa', 'fa-graduation-cap', 'text-secondary', 5],
            ];
        @endphp
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title mb-0">
                    <i class="fa-solid fa-users text-primary"></i>
                    Daftar Akun Pengguna Terdaftar
                </h5>
                <small class="text-muted">
                    Total: {{ number_format($totalSemua, 0, ',', '.') }} akun terdaftar
                    @if($filterAktif) &middot; {{ number_format($users->total(), 0, ',', '.') }} cocok dengan filter @endif
                </small>
            </div>

            <!-- Bar Aksi Hapus Massal (Muncul Otomatis Saat Ada Akun Dicentang) -->
            <div id="bulkActionBar" class="alert alert-danger mx-3 mt-3 py-2 px-3 d-none align-items-center justify-content-between rounded-3 shadow-sm border-0 animate-fade-in" style="background: linear-gradient(135deg, #e5484d, #b91c1c);">
                <div class="d-flex align-items-center gap-2 text-white">
                    <span class="badge bg-white text-danger fw-bold fs-6 px-2 py-1 shadow-xs" id="bulkSelectedCount">0</span>
                    <span class="fw-semibold small">Akun pengguna telah dipilih</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm fw-semibold" id="btnCancelBulkSelect">Batal</button>
                    <button type="button" class="btn btn-warning btn-sm fw-bold shadow-xs text-dark" data-bs-toggle="modal" data-bs-target="#modalBulkDelete">
                        <i class="fa-solid fa-trash-can me-1"></i> Hapus Akun Terpilih
                    </button>
                </div>
            </div>

            <!-- Tab Role (link, bukan JavaScript, agar halaman ringan) -->
            <div class="border-bottom px-3 pt-2">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-auto" style="white-space:nowrap;">
                    @foreach($tabRole as $kode => [$label, $ikon, $warna, $idRole])
                        @php $jumlah = $idRole === null ? $totalSemua : ($countPerRole[$idRole] ?? 0); @endphp
                        <li class="nav-item">
                            <a class="nav-link small py-2 fw-semibold {{ (string) $roleFilter === (string) $kode ? 'active' : '' }}"
                               href="{{ route('akun.index', array_filter(['role' => $kode, 'q' => $q, 'kelas' => $kode === '5' || $kode === '' ? $kelasFilter : null], fn($v) => $v !== null && $v !== '')) }}">
                                <i class="fa-solid {{ $ikon }} me-1 {{ $warna }}"></i> {{ $label }}
                                <span class="badge rounded-pill bg-light text-dark border ms-1">{{ number_format($jumlah, 0, ',', '.') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Pencarian & Filter Kelas -->
            <form method="GET" action="{{ route('akun.index') }}" class="px-3 py-3 border-bottom bg-light">
                <input type="hidden" name="role" value="{{ $roleFilter }}">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Cari username, nama, atau email..." autocomplete="off">
                        </div>
                    </div>
                    <div class="col-8 col-md-4">
                        <select name="kelas" class="form-select form-select-sm" aria-label="Filter kelas siswa">
                            <option value="">Semua kelas (siswa)</option>
                            @foreach($daftarKelas as $k)
                                <option value="{{ $k->id_kelas }}" {{ (string) $kelasFilter === (string) $k->id_kelas ? 'selected' : '' }}>
                                    {{ trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? $k->nama_jurusan ?? $k->id_kelas)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">Cari</button>
                        @if($filterAktif)
                            <a href="{{ route('akun.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset filter"><i class="fa-solid fa-xmark"></i></a>
                        @endif
                    </div>
                </div>
            </form>

            @include('jadwal._tabel_pengguna', [
                'userList' => $users,
                'emptyMessage' => $filterAktif ? 'Tidak ada akun yang cocok dengan pencarian/filter.' : 'Belum ada akun yang terdaftar.',
            ])

            <!-- Navigasi Halaman -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 border-top">
                <span class="small text-muted">
                    @if($users->total() > 0)
                        Menampilkan {{ $users->firstItem() }}&ndash;{{ $users->lastItem() }} dari {{ number_format($users->total(), 0, ',', '.') }} akun
                    @else
                        Tidak ada data
                    @endif
                </span>
                @if($users->hasPages())
                    <div class="overflow-auto">{{ $users->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL HAPUS MASSAL PENGGUNA (BULK DELETE)                -->
<!-- ======================================================== -->
<div class="modal fade" id="modalBulkDelete" tabindex="-1" aria-labelledby="modalBulkDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalBulkDeleteLabel">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Konfirmasi Hapus Massal Akun
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('akun.bulkDelete') }}" method="POST" id="formBulkDelete">
                @csrf
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <span class="p-3 bg-danger-subtle text-danger rounded-circle d-inline-flex fs-2">
                            <i class="fa-solid fa-trash-can"></i>
                        </span>
                    </div>
                    <h5 class="fw-bold mb-2">Hapus <span id="modalBulkCountText" class="text-danger fw-bold">0</span> Akun Pengguna?</h5>
                    <p class="text-muted small mb-3">
                        Tindakan ini permanen. Semua akun yang Anda centang akan dihapus dari sistem beserta seluruh akses loginnya.
                    </p>
                    
                    <div class="text-start mb-2">
                        <small class="text-muted fw-semibold">Daftar Akun yang Akan Dihapus:</small>
                    </div>
                    <div id="bulkUserPreviewList" class="text-start bg-light p-3 rounded-3 small border overflow-auto mb-3" style="max-height: 150px;">
                        <!-- Daftar nama akun yang dipilih -->
                    </div>

                    <div id="bulkHiddenInputsContainer">
                        <!-- Input hidden ids[] -->
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">
                        <i class="fa-solid fa-trash-can me-1"></i> Ya, Hapus Semua Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL IMPORT PENGGUNA & DAFTAR SISWA VIA EXCEL / CSV     -->
<!-- ======================================================== -->
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-labelledby="modalImportExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalImportExcelLabel">
                    <i class="fa-solid fa-file-excel"></i>
                    Import Data Siswa / Pengguna dari Excel & CSV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('akun.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <!-- Panduan Fleksibel -->
                    <div class="alert alert-success border-0 bg-success-subtle p-3 mb-4 rounded-3">
                        <h6 class="fw-bold text-success mb-2 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-check"></i> Sistem Mendukung 2 Format Sekaligus:
                        </h6>
                        <ul class="mb-0 ps-3 small text-secondary">
                            <li class="mb-1">
                                <strong>Format 1 (Format Daftar Hadir Siswa / Presensi Kelas)</strong>:
                                <br>Langsung upload file Excel presensi kelas sekolah (memuat judul <code>KELAS : X RPL 1</code> atau nama sheet <code>X RPL 1</code>, serta kolom <code>NIS</code> dan <code>NAMA SISWA</code>). Sistem otomatis mendeteksi kelas dan membuatkan akun Siswa (password default: <code>123456</code>).
                            </li>
                            <li>
                                <strong>Format 2 (Tabel Akun Umum)</strong>:
                                <br>Kolom: <code>username</code>, <code>nama_lengkap</code>, <code>password</code>, <code>role</code>, <code>no_hp</code>.
                            </li>
                        </ul>
                    </div>

                    <!-- Tombol Unduh Template -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                            <div>
                                <span class="fw-semibold text-dark d-block">Pilihan Unduh Format:</span>
                                <small class="text-muted">Unduh contoh format file jika ingin memeriksa susunan kolom.</small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('akun.template.siswa') }}" class="btn btn-outline-success btn-sm fw-semibold">
                                    <i class="fa-solid fa-file-excel me-1"></i> Format Daftar Hadir Siswa
                                </a>
                                <a href="{{ route('akun.template') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                                    <i class="fa-solid fa-download me-1"></i> Format Akun Umum
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Input Pilihan Role Pengguna (Opsional) -->
                    <div class="mb-3">
                        <label for="default_role" class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                            <span>Peran / Role Pengguna <span class="badge bg-light text-muted border fw-normal">Opsional</span></span>
                        </label>
                        <select name="default_role" id="default_role" class="form-select">
                            <option value="">-- Otomatis (Dari kolom file Excel / Siswa jika format daftar hadir) --</option>
                            <option value="2">Guru Pengampu Mata Pelajaran</option>
                            <option value="3">Wali Kelas (Verifikasi Izin Siswa)</option>
                            <option value="7">Guru BK (Bimbingan Konseling)</option>
                            <option value="4">Waka Kurikulum / Administrator</option>
                            <option value="5">Siswa</option>
                        </select>
                        <div class="form-text small text-muted">
                            Pilih role jika mengimpor daftar Guru, Wali Kelas, atau BK tanpa menulis kolom role di Excel.
                        </div>
                    </div>

                    <!-- Input Pilihan Target Kelas (Opsional) -->
                    <div class="mb-3">
                        <label for="target_id_kelas" class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                            <span>Target Kelas Siswa <span class="badge bg-light text-muted border fw-normal">Khusus Siswa</span></span>
                        </label>
                        <select name="target_id_kelas" id="target_id_kelas" class="form-select">
                            <option value="">-- Otomatis Deteksi dari Judul "KELAS : X RPL 1" / Nama Sheet (Direkomendasikan) --</option>
                            @if(isset($daftarKelas))
                                @foreach($daftarKelas as $k)
                                    <option value="{{ $k->id_kelas }}">
                                        {{ $k->tingkat }} {{ $k->id_jurusan ?? $k->nama_jurusan ?? $k->id_kelas }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div class="form-text small text-muted">
                            Hanya digunakan jika mengimpor siswa dan file tidak memuat keterangan nama kelas.
                        </div>
                    </div>

                    <!-- Input File -->
                    <div class="mb-3">
                        <label for="file_excel" class="form-label fw-bold text-dark">
                            Pilih File Excel / CSV <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-file-arrow-up text-success"></i></span>
                            <input type="file" class="form-control" id="file_excel" name="file_excel" accept=".xlsx, .csv" required>
                        </div>
                        <div class="form-text small text-muted">
                            Mendukung file Excel berekstensi <strong>.xlsx</strong> (bisa multi-sheet untuk banyak kelas sekaligus) atau <strong>.csv</strong>. Maksimal 10 MB.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Mulai Import Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL EDIT & HAPUS PENGGUNA (satu modal dipakai bersama) -->
<!-- ======================================================== -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalEditUserLabel">
                    <i class="fa-solid fa-user-pen text-primary me-1"></i> Edit Akun Pengguna
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditUser" action="#" method="POST" data-action-template="{{ route('akun.update', '__ID__') }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="edit_username">Username / NIP / NISN <span class="text-danger">*</span></label>
                        <input type="text" id="edit_username" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_nama">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" id="edit_nama" name="nama_lengkap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_email">Email (untuk OTP)</label>
                        <input type="email" id="edit_email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_role">Hak Akses / Role <span class="text-danger">*</span></label>
                        <select id="edit_role" name="id_role" class="form-select" required>
                            <option value="4">Waka Kurikulum / Administrator</option>
                            <option value="2">Guru Pengampu Mata Pelajaran</option>
                            <option value="3">Wali Kelas (Verifikasi Izin)</option>
                            <option value="7">Guru BK (Verifikasi Izin Siswa)</option>
                            <option value="5">Siswa</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit_status">Status Akun <span class="text-danger">*</span></label>
                        <select id="edit_status" name="is_aktif" class="form-select" required>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif (Diblokir)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="edit_pass">Ganti Password Baru (Opsional)</label>
                        <input type="password" id="edit_pass" name="password" class="form-control" placeholder="Biarkan kosong jika tidak ingin diubah" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-labelledby="modalDeleteUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold" id="modalDeleteUserLabel">Konfirmasi Hapus Akun</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDeleteUser" action="#" method="POST" data-action-template="{{ route('akun.delete', '__ID__') }}">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center p-4">
                    <i class="fa-solid fa-trash-can fa-3x text-danger mb-3 d-block"></i>
                    <p class="mb-1 fw-bold text-dark" id="delete_nama"></p>
                    <p class="small text-muted mb-0">Username: <code id="delete_username"></code></p>
                    <div class="alert alert-warning small mt-3 mb-0 text-start">
                        Tindakan ini permanen dan akan menghapus akses login akun tersebut dari sistem.
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can me-1"></i> Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Isi modal Edit / Hapus dari atribut data tombol yang diklik
        document.addEventListener('click', function (e) {
            const edit = e.target.closest('.btn-edit-user');
            if (edit) {
                const f = document.getElementById('formEditUser');
                f.action = f.dataset.actionTemplate.replace('__ID__', edit.dataset.id);
                document.getElementById('edit_username').value = edit.dataset.username || '';
                document.getElementById('edit_nama').value = edit.dataset.nama || '';
                document.getElementById('edit_email').value = edit.dataset.email || '';
                document.getElementById('edit_role').value = edit.dataset.role || '';
                document.getElementById('edit_status').value = edit.dataset.aktif === '0' ? '0' : '1';
                document.getElementById('edit_pass').value = '';
                return;
            }
            const del = e.target.closest('.btn-delete-user');
            if (del) {
                const f = document.getElementById('formDeleteUser');
                f.action = f.dataset.actionTemplate.replace('__ID__', del.dataset.id);
                document.getElementById('delete_nama').textContent = del.dataset.nama || '';
                document.getElementById('delete_username').textContent = del.dataset.username || '';
            }
        });

        // Hapus massal (hanya akun pada halaman yang sedang dibuka)
        const bulkBar = document.getElementById('bulkActionBar');
        const countBadge = document.getElementById('bulkSelectedCount');
        const modalCountText = document.getElementById('modalBulkCountText');
        const hiddenContainer = document.getElementById('bulkHiddenInputsContainer');
        const previewList = document.getElementById('bulkUserPreviewList');
        const btnCancel = document.getElementById('btnCancelBulkSelect');

        function esc(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function updateBulkUI() {
            const checked = document.querySelectorAll('.user-select-checkbox:checked');
            if (checked.length > 0) {
                bulkBar?.classList.remove('d-none');
                bulkBar?.classList.add('d-flex');
                if (countBadge) countBadge.textContent = checked.length;
                if (modalCountText) modalCountText.textContent = checked.length;
                if (hiddenContainer) {
                    hiddenContainer.innerHTML = '';
                    checked.forEach(cb => {
                        const h = document.createElement('input');
                        h.type = 'hidden'; h.name = 'ids[]'; h.value = cb.value;
                        hiddenContainer.appendChild(h);
                    });
                }
                if (previewList) {
                    previewList.innerHTML = '';
                    checked.forEach(cb => {
                        const item = document.createElement('div');
                        item.className = 'd-flex justify-content-between align-items-center py-1 border-bottom border-light';
                        item.innerHTML = '<span><strong>' + esc(cb.dataset.name || '-') + '</strong></span> <code class="text-danger small">' + esc(cb.dataset.username || '-') + '</code>';
                        previewList.appendChild(item);
                    });
                }
            } else {
                bulkBar?.classList.add('d-none');
                bulkBar?.classList.remove('d-flex');
                if (hiddenContainer) hiddenContainer.innerHTML = '';
                if (previewList) previewList.innerHTML = '';
            }
        }

        document.querySelectorAll('.check-all-table').forEach(checkAll => {
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('.user-select-checkbox').forEach(cb => { cb.checked = this.checked; });
                updateBulkUI();
            });
        });

        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('user-select-checkbox')) updateBulkUI();
        });

        btnCancel?.addEventListener('click', function () {
            document.querySelectorAll('.user-select-checkbox:checked').forEach(cb => { cb.checked = false; });
            document.querySelectorAll('.check-all-table').forEach(cb => { cb.checked = false; });
            updateBulkUI();
        });
    });
</script>
@endpush
