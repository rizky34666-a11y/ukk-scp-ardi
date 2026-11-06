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
    <div class="col-lg-4">
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
    <div class="col-lg-8">
        <div class="app-card">
            <!-- Header Card & Search -->
            <div class="app-card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h5 class="app-card-title mb-0">
                        <i class="fa-solid fa-users text-primary"></i>
                        Daftar Akun Pengguna Terdaftar
                    </h5>
                    <small class="text-muted">Total: {{ count($users) }} akun terdaftar dalam sistem</small>
                </div>
                <div style="min-width: 220px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="userTableSearch" class="form-control" placeholder="Cari nama atau username...">
                    </div>
                </div>
            </div>

            <!-- Bar Aksi Hapus Massal (Muncul Otomatis Saat Ada Akun Dicentang) -->
            <div id="bulkActionBar" class="alert alert-danger mx-3 mt-3 py-2 px-3 d-none align-items-center justify-content-between rounded-3 shadow-sm border-0 animate-fade-in" style="background: linear-gradient(135deg, #e5484d, #b91c1c);">
                <div class="d-flex align-items-center gap-2 text-white">
                    <span class="badge bg-white text-danger fw-bold fs-6 px-2 py-1 shadow-xs" id="bulkSelectedCount">0</span>
                    <span class="fw-semibold small">Akun pengguna telah dipilih</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm fw-semibold" id="btnCancelBulkSelect">
                        Batal
                    </button>
                    <button type="button" class="btn btn-warning btn-sm fw-bold shadow-xs text-dark" data-bs-toggle="modal" data-bs-target="#modalBulkDelete">
                        <i class="fa-solid fa-trash-can me-1"></i> Hapus Akun Terpilih
                    </button>
                </div>
            </div>

            <!-- Tab Navigasi Role -->
            @php
                $countWaka = $users->where('id_role', 4)->count();
                $countGuru = $users->where('id_role', 2)->count();
                $countWali = $users->where('id_role', 3)->count();
                $countBK = $users->where('id_role', 7)->count();
                $countSiswa = $users->where('id_role', 5)->count();
            @endphp
            <div class="border-bottom px-3 pt-2">
                <ul class="nav nav-tabs border-0" id="roleTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active small py-2 fw-semibold" id="tab-guru" data-bs-toggle="tab" data-bs-target="#pane-guru" type="button" role="tab">
                            <i class="fa-solid fa-chalkboard-user me-1 text-primary"></i> Guru Pengampu
                            <span class="badge rounded-pill bg-primary-subtle text-primary ms-1">{{ $countGuru }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small py-2 fw-semibold" id="tab-wali" data-bs-toggle="tab" data-bs-target="#pane-wali" type="button" role="tab">
                            <i class="fa-solid fa-user-tie me-1 text-success"></i> Wali Kelas
                            <span class="badge rounded-pill bg-success-subtle text-success ms-1">{{ $countWali }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small py-2 fw-semibold" id="tab-bk" data-bs-toggle="tab" data-bs-target="#pane-bk" type="button" role="tab">
                            <i class="fa-solid fa-user-doctor me-1 text-info"></i> Guru BK
                            <span class="badge rounded-pill bg-info-subtle text-info ms-1">{{ $countBK }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small py-2 fw-semibold" id="tab-waka" data-bs-toggle="tab" data-bs-target="#pane-waka" type="button" role="tab">
                            <i class="fa-solid fa-user-shield me-1 text-danger"></i> Waka Kurikulum
                            <span class="badge rounded-pill bg-danger-subtle text-danger ms-1">{{ $countWaka }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small py-2 fw-semibold" id="tab-siswa" data-bs-toggle="tab" data-bs-target="#pane-siswa" type="button" role="tab">
                            <i class="fa-solid fa-graduation-cap me-1 text-secondary"></i> Siswa
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary ms-1">{{ $countSiswa }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content Tiap Role -->
            <div class="tab-content" id="roleTabContent">
                <!-- Tab 1: Guru Pengampu -->
                <div class="tab-pane fade show active" id="pane-guru" role="tabpanel">
                    @include('jadwal._tabel_pengguna', [
                        'userList' => $users->where('id_role', 2),
                        'emptyMessage' => 'Belum ada akun Guru Pengampu yang terdaftar.'
                    ])
                </div>

                <!-- Tab 2: Wali Kelas -->
                <div class="tab-pane fade" id="pane-wali" role="tabpanel">
                    @include('jadwal._tabel_pengguna', [
                        'userList' => $users->where('id_role', 3),
                        'emptyMessage' => 'Belum ada akun Wali Kelas yang terdaftar.'
                    ])
                </div>

                <!-- Tab 3: Guru BK -->
                <div class="tab-pane fade" id="pane-bk" role="tabpanel">
                    @include('jadwal._tabel_pengguna', [
                        'userList' => $users->where('id_role', 7),
                        'emptyMessage' => 'Belum ada akun Guru BK yang terdaftar.'
                    ])
                </div>

                <!-- Tab 4: Waka Kurikulum -->
                <div class="tab-pane fade" id="pane-waka" role="tabpanel">
                    @include('jadwal._tabel_pengguna', [
                        'userList' => $users->where('id_role', 4),
                        'emptyMessage' => 'Belum ada akun Waka Kurikulum yang terdaftar.'
                    ])
                </div>

                <!-- Tab 5: Siswa -->
                <div class="tab-pane fade" id="pane-siswa" role="tabpanel">
                    <!-- Filter Kelas Siswa -->
                    <div class="px-3 pt-3 pb-2 border-bottom bg-light">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <i class="fa-solid fa-filter text-secondary small"></i>
                            <span class="small fw-semibold text-muted">Filter Kelas:</span>
                            <select id="filterKelasSiswa" class="form-select form-select-sm" style="max-width: 250px;">
                                <option value="">— Semua Kelas —</option>
                                @foreach($daftarKelas as $k)
                                    <option value="{{ $k->id_kelas }}"
                                            data-name="{{ strtolower(trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? ''))) }}">
                                        {{ trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? $k->nama_jurusan ?? $k->id_kelas)) }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="siswaFilterCount" class="badge bg-primary-subtle text-primary border border-primary-subtle small d-none"></span>
                        </div>
                    </div>
                    @include('jadwal._tabel_pengguna', [
                        'userList'     => $users->where('id_role', 5),
                        'emptyMessage' => 'Belum ada akun Siswa yang terdaftar.',
                        'daftarKelas'  => $daftarKelas,
                    ])
                </div>
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
<!-- MODAL EDIT PENGGUNA                                      -->
<!-- ======================================================== -->
@foreach($users as $u)
    <div class="modal fade" id="modalEditUser{{ $u->id_user }}" tabindex="-1" aria-labelledby="modalEditUserLabel{{ $u->id_user }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalEditUserLabel{{ $u->id_user }}">
                        <i class="fa-solid fa-user-pen text-primary me-1"></i> Edit Akun Pengguna
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('akun.update', $u->id_user) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="edit_username_{{ $u->id_user }}">Username / NIP / NISN <span class="text-danger">*</span></label>
                            <input type="text" id="edit_username_{{ $u->id_user }}" name="username" class="form-control" value="{{ old('username', $u->username) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_nama_{{ $u->id_user }}">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                            <input type="text" id="edit_nama_{{ $u->id_user }}" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap', $u->nama_lengkap) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_email_{{ $u->id_user }}">Email (untuk OTP)</label>
                            <input type="email" id="edit_email_{{ $u->id_user }}" name="email" class="form-control" value="{{ old('email', $u->email) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_role_{{ $u->id_user }}">Hak Akses / Role <span class="text-danger">*</span></label>
                            <select id="edit_role_{{ $u->id_user }}" name="id_role" class="form-select" required>
                                <option value="4" {{ $u->id_role == 4 ? 'selected' : '' }}>Waka Kurikulum / Administrator</option>
                                <option value="2" {{ $u->id_role == 2 ? 'selected' : '' }}>Guru Pengampu Mata Pelajaran</option>
                                <option value="3" {{ $u->id_role == 3 ? 'selected' : '' }}>Wali Kelas (Verifikasi Izin)</option>
                                <option value="7" {{ $u->id_role == 7 ? 'selected' : '' }}>Guru BK (Verifikasi Izin Siswa)</option>
                                <option value="5" {{ $u->id_role == 5 ? 'selected' : '' }}>Siswa</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_status_{{ $u->id_user }}">Status Akun <span class="text-danger">*</span></label>
                            <select id="edit_status_{{ $u->id_user }}" name="is_aktif" class="form-select" required>
                                <option value="1" {{ $u->is_aktif == 1 ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ $u->is_aktif == 0 ? 'selected' : '' }}>Nonaktif (Diblokir)</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label" for="edit_pass_{{ $u->id_user }}">Ganti Password Baru (Opsional)</label>
                            <input type="password" id="edit_pass_{{ $u->id_user }}" name="password" class="form-control" placeholder="Biarkan kosong jika tidak ingin diubah">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Akun -->
    @if(session('id_user') != $u->id_user)
        <div class="modal fade" id="modalDeleteUser{{ $u->id_user }}" tabindex="-1" aria-labelledby="modalDeleteUserLabel{{ $u->id_user }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h6 class="modal-title fw-bold" id="modalDeleteUserLabel{{ $u->id_user }}">Konfirmasi Hapus Akun</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('akun.delete', $u->id_user) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body text-center p-4">
                            <i class="fa-solid fa-trash-can fa-3x text-danger mb-3 d-block"></i>
                            <p class="mb-1 fw-bold text-dark">{{ $u->nama_lengkap }}</p>
                            <p class="small text-muted mb-0">
                                Username: <code>{{ $u->username }}</code>
                            </p>
                            <div class="alert alert-warning small mt-3 mb-0 text-start">
                                Tindakan ini permanen dan akan menghapus akses login akun tersebut dari sistem.
                            </div>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fa-solid fa-trash-can me-1"></i> Ya, Hapus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Real-time Search Filter pada tabel pengguna (Hanya 5 akun default, semua jika dicari)
        const searchInput = document.getElementById('userTableSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const allRows = document.querySelectorAll('.user-searchable-table tbody tr.user-row');
                const moreBanners = document.querySelectorAll('.user-more-banner');

                // Jika sedang mengetik di kotak cari, reset dropdown filter kelas
                if (query !== '') {
                    const selKelas = document.getElementById('filterKelasSiswa');
                    if (selKelas) selKelas.value = '';
                    const badgeK = document.getElementById('siswaFilterCount');
                    if (badgeK) badgeK.classList.add('d-none');
                }

                if (query === '') {
                    // Reset ke mode awal: hanya 5 baris pertama per tabel yang ditampilkan
                    document.querySelectorAll('.user-searchable-table tbody').forEach(tbody => {
                        const rows = tbody.querySelectorAll('tr.user-row');
                        rows.forEach((row, idx) => {
                            if (idx < 5) {
                                row.classList.remove('d-none');
                                row.style.display = '';
                            } else {
                                row.classList.add('d-none');
                                row.style.display = 'none';
                            }
                        });
                    });
                    moreBanners.forEach(b => b.classList.remove('d-none'));
                } else {
                    // Mode pencarian aktif: tampilkan semua yang cocok dengan query
                    allRows.forEach(row => {
                        const searchData = row.getAttribute('data-search') || '';
                        if (searchData.includes(query)) {
                            row.classList.remove('d-none');
                            row.style.display = '';
                        } else {
                            row.classList.add('d-none');
                            row.style.display = 'none';
                        }
                    });
                    moreBanners.forEach(b => b.classList.add('d-none'));
                }
            });
        }

        // Tombol Toggle "Tampilkan Semua" / "Sembunyikan"
        document.querySelectorAll('.btn-toggle-all-rows').forEach(btn => {
            btn.addEventListener('click', function () {
                const tableContainer = this.closest('.table-responsive');
                const tbody = tableContainer ? tableContainer.querySelector('tbody') : null;
                if (!tbody) return;

                const hiddenRows = tbody.querySelectorAll('tr.user-row-overflow');
                const totalRows = tbody.querySelectorAll('tr.user-row').length;
                const isExpanded = this.getAttribute('data-expanded') === 'true';

                if (isExpanded) {
                    hiddenRows.forEach(r => {
                        r.classList.add('d-none');
                        r.style.display = 'none';
                    });
                    this.textContent = `Tampilkan Semua (${totalRows})`;
                    this.setAttribute('data-expanded', 'false');
                } else {
                    hiddenRows.forEach(r => {
                        r.classList.remove('d-none');
                        r.style.display = '';
                    });
                    this.textContent = 'Sembunyikan (Tampilkan 5 saja)';
                    this.setAttribute('data-expanded', 'true');
                }
            });
        });

        // 2. Fitur Hapus Massal (Bulk Delete)
        const bulkBar = document.getElementById('bulkActionBar');
        const countBadge = document.getElementById('bulkSelectedCount');
        const modalCountText = document.getElementById('modalBulkCountText');
        const hiddenContainer = document.getElementById('bulkHiddenInputsContainer');
        const previewList = document.getElementById('bulkUserPreviewList');
        const btnCancel = document.getElementById('btnCancelBulkSelect');

        function updateBulkUI() {
            const checkedBoxes = document.querySelectorAll('.user-select-checkbox:checked');
            const totalSelected = checkedBoxes.length;

            if (totalSelected > 0) {
                bulkBar?.classList.remove('d-none');
                bulkBar?.classList.add('d-flex');
                if (countBadge) countBadge.textContent = totalSelected;
                if (modalCountText) modalCountText.textContent = totalSelected;

                // Siapkan hidden input dan preview untuk modal konfirmasi
                if (hiddenContainer) {
                    hiddenContainer.innerHTML = '';
                    checkedBoxes.forEach(cb => {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'ids[]';
                        hidden.value = cb.value;
                        hiddenContainer.appendChild(hidden);
                    });
                }

                if (previewList) {
                    previewList.innerHTML = '';
                    checkedBoxes.forEach(cb => {
                        const name = cb.getAttribute('data-name') || '-';
                        const username = cb.getAttribute('data-username') || '-';
                        const item = document.createElement('div');
                        item.className = 'd-flex justify-content-between align-items-center py-1 border-bottom border-light';
                        item.innerHTML = `<span><strong>${name}</strong></span> <code class="text-danger small">${username}</code>`;
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

        // Checkbox "Pilih Semua" pada tiap header tabel
        document.querySelectorAll('.check-all-table').forEach(checkAll => {
            checkAll.addEventListener('change', function () {
                const table = this.closest('table');
                if (!table) return;

                const isChecked = this.checked;
                // Hanya pilih baris yang terlihat (tidak disembunyikan pencarian)
                const visibleCheckboxes = table.querySelectorAll('tbody tr.user-row:not([style*="display: none"]) .user-select-checkbox');
                visibleCheckboxes.forEach(cb => {
                    cb.checked = isChecked;
                });

                updateBulkUI();
            });
        });

        // Event listener saat checkbox individu diklik
        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('user-select-checkbox')) {
                updateBulkUI();
            }
        });

        // Tombol Batal pilihan massal
        btnCancel?.addEventListener('click', function () {
            document.querySelectorAll('.user-select-checkbox:checked').forEach(cb => {
                cb.checked = false;
            });
            document.querySelectorAll('.check-all-table').forEach(cb => {
                cb.checked = false;
            });
            updateBulkUI();
        });

        // 3. Filter Kelas pada Tab Siswa
        const filterKelasSiswa = document.getElementById('filterKelasSiswa');
        if (filterKelasSiswa) {
            filterKelasSiswa.addEventListener('change', function () {
                const selectedId = this.value.trim();
                const selectedOption = this.options[this.selectedIndex];
                const selectedName = selectedOption ? (selectedOption.getAttribute('data-name') || '').trim().toLowerCase() : '';
                const pane = document.getElementById('pane-siswa');
                if (!pane) return;

                // Reset kotak pencarian global jika memilih filter kelas
                const searchInput = document.getElementById('userTableSearch');
                if (searchInput && searchInput.value.trim() !== '') {
                    searchInput.value = '';
                }

                const rows = pane.querySelectorAll('tbody tr.user-row');
                const moreBanner = pane.querySelector('.user-more-banner');
                const countBadgeKelas = document.getElementById('siswaFilterCount');
                let visible = 0;

                const cleanSelected = selectedName.replace(/[^a-z0-9]/g, '');

                rows.forEach(row => {
                    const rowKelasId = (row.getAttribute('data-kelas-id') || '').trim();
                    const rowKelasLabel = (row.getAttribute('data-kelas-label') || '').trim().toLowerCase();
                    const cleanRow = rowKelasLabel.replace(/[^a-z0-9]/g, '');

                    // Pencocokan:
                    // 1. Kosong: tampilkan mode awal
                    // 2. ID kelas persis sama (misal '48' == '48')
                    // 3. Nama kelas alfanumerik cocok (misal 'xiibd' == 'xiibd' atau 'xrpl1' == 'xrpl1')
                    const isMatch = (selectedId === '') ||
                                    (rowKelasId !== '' && rowKelasId === selectedId) ||
                                    (cleanSelected !== '' && (cleanRow === cleanSelected || cleanRow.includes(cleanSelected)));

                    if (isMatch && selectedId !== '') {
                        row.classList.remove('d-none');
                        row.style.display = '';
                        visible++;
                    } else if (selectedId === '') {
                        // Akan diatur oleh blok reset di bawah
                    } else {
                        row.classList.add('d-none');
                        row.style.display = 'none';
                    }
                });

                // Sembunyikan banner "Tampilkan Semua" ketika filter kelas aktif
                if (moreBanner) {
                    moreBanner.classList.toggle('d-none', selectedId !== '');
                }

                // Update badge jumlah siswa terfilter
                if (countBadgeKelas) {
                    if (selectedId !== '') {
                        countBadgeKelas.textContent = visible + ' siswa ditemukan';
                        countBadgeKelas.classList.remove('d-none');
                    } else {
                        countBadgeKelas.classList.add('d-none');
                        // Reset ke mode awal: hanya tampilkan 5 baris pertama
                        rows.forEach((row, idx) => {
                            if (idx < 5) {
                                row.classList.remove('d-none');
                                row.style.display = '';
                            } else {
                                row.classList.add('d-none');
                                row.style.display = 'none';
                            }
                        });
                        if (moreBanner) moreBanner.classList.remove('d-none');
                    }
                }
            });
        }
    });
</script>
@endpush
