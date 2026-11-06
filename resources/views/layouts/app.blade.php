<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UKK SCP - Sistem Manajemen KBM & Perizinan')</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Custom Modern Style -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui-polish.css') }}?v={{ @filemtime(public_path('css/ui-polish.css')) }}">
    
    @stack('styles')
</head>
<body>

    @php
        $role = session('id_role');
        $homeRoute = route('login');
        if ($role == 4) $homeRoute = route('waka.dashboard');
        elseif ($role == 2) $homeRoute = route('guru.dashboard');
        elseif ($role == 3) $homeRoute = route('guru.dashboard');
        elseif ($role == 7) $homeRoute = route('kesiswaan.izin.index');
        elseif ($role == 5) $homeRoute = route('siswa.izin.index');

        $roleName = 'Pengguna';
        if ($role == 4) $roleName = 'Waka Kurikulum';
        elseif ($role == 2) $roleName = 'Guru Pengampu';
        elseif ($role == 3) $roleName = 'Wali Kelas & Guru';
        elseif ($role == 7) $roleName = 'Guru BK';
        elseif ($role == 5) $roleName = 'Siswa';

        $currentUser = null;
        if (session()->has('id_user')) {
            $currentUser = \Illuminate\Support\Facades\DB::table('users')->where('id_user', session('id_user'))->first();
        }
        $authUserEmail = $currentUser?->email ?? '';
        $authUserFotoUrl = null;
        if (!empty($currentUser?->foto_profil) && file_exists(public_path('uploads/profil/' . basename($currentUser->foto_profil)))) {
            $authUserFotoUrl = asset('uploads/profil/' . basename($currentUser->foto_profil));
        }
        $maskedUserEmail = \App\Services\OtpService::maskEmail($authUserEmail);

        // Badge counter dinamis untuk peningkatan UX navigasi
        $pendingIzinCount = 0;
        if (in_array($role, [3, 4, 7])) {
            $pendingIzinCount = \App\Models\LeaveRequest::where('status_approval', 'Pending')->count();
        }

        $guruOverdueCount = 0;
        if (in_array($role, [2, 3])) {
            $guruOverdueCount = isset($overdueJadwals) ? $overdueJadwals->count() : 0;
        }

        $siswaPendingCount = 0;
        if ($role == 5 && session()->has('username')) {
            $siswaUser = \App\Models\Siswa::where('nisn', session('username'))->first();
            if ($siswaUser) {
                $siswaPendingCount = \App\Models\LeaveRequest::where('student_id', $siswaUser->id_siswa)
                    ->where('status_approval', 'Pending')
                    ->count();
            }
        }
    @endphp

    <div class="app-wrapper">
        <!-- Backdrop Overlay untuk Mobile Sidebar -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- ==================== SIDEBAR UTAMA ==================== -->
        <aside class="app-sidebar" id="appSidebar">
            <!-- Header Brand / Logo -->
            <div class="sidebar-brand-wrapper">
                <a class="sidebar-brand" href="{{ $homeRoute }}">
                    <div class="sidebar-brand-icon">
                        <img src="{{ asset('image/logosmk.png') }}" alt="Logo SMK">
                    </div>

                    <div class="sidebar-brand-text">
                        <span class="brand-name">UKK SCP</span>
                        <span class="brand-subtitle">KBM & Perizinan</span>
                    </div>
                </a>
                <button type="button" class="sidebar-close-btn d-lg-none" id="sidebarCloseBtn" aria-label="Tutup Menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Navigasi Menu Sesuai Role -->
            <nav class="sidebar-nav">
                @if($role == 4)
                    <!-- Role: Waka Kurikulum -->
                    <div class="sidebar-section-label">Pusat Kendali</div>
                    <a class="sidebar-link {{ request()->routeIs('waka.dashboard') ? 'active' : '' }}" href="{{ route('waka.dashboard') }}">
                        <i class="fa-solid fa-gauge-high nav-icon"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>

                    <div class="sidebar-section-label">Akademik & Jadwal</div>
                    <a class="sidebar-link {{ request()->routeIs('jadwal.*') ? 'active' : '' }}" href="{{ route('jadwal.index') }}">
                        <i class="fa-regular fa-calendar-days nav-icon"></i>
                        <span class="nav-text">Kelola Jadwal</span>
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('waka.jurnal.*') ? 'active' : '' }}" href="{{ route('waka.jurnal.rekap') }}">
                        <i class="fa-solid fa-clipboard-check nav-icon"></i>
                        <span class="nav-text">Rekap Jurnal</span>
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('rekap.absensi.*') ? 'active' : '' }}" href="{{ route('rekap.absensi.index') }}">
                        <i class="fa-solid fa-user-xmark nav-icon"></i>
                        <span class="nav-text">Rekap Absensi Siswa</span>
                        @if($pendingIzinCount > 0)
                            <span class="badge rounded-pill bg-warning text-dark ms-auto" style="font-size: 0.68rem;" title="{{ $pendingIzinCount }} permohonan izin baru">
                                {{ $pendingIzinCount }} Baru
                            </span>
                        @endif
                    </a>

                    <div class="sidebar-section-label">Konfigurasi</div>
                    <a class="sidebar-link {{ request()->routeIs('akun.*') ? 'active' : '' }}" href="{{ route('akun.index') }}">
                        <i class="fa-solid fa-users-gear nav-icon"></i>
                        <span class="nav-text">Manajemen Akun</span>
                    </a>

                @elseif($role == 2)
                    <!-- Role: Guru Pengampu -->
                    <div class="sidebar-section-label">Menu Utama</div>
                    <a class="sidebar-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}" href="{{ route('guru.dashboard') }}">
                        <i class="fa-solid fa-gauge-high nav-icon"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>

                    <div class="sidebar-section-label">Aktivitas Mengajar</div>
                    <a class="sidebar-link {{ request()->routeIs('jadwal.*') ? 'active' : '' }}" href="{{ route('jadwal.index') }}">
                        <i class="fa-regular fa-calendar-days nav-icon"></i>
                        <span class="nav-text">Jadwal Pelajaran</span>
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('jurnal.*') ? 'active' : '' }}" href="{{ route('jurnal.index') }}">
                        <i class="fa-solid fa-book-bookmark nav-icon"></i>
                        <span class="nav-text">Jurnal Mengajar</span>
                        @if($guruOverdueCount > 0)
                            <span class="badge rounded-pill bg-danger ms-auto" style="font-size: 0.68rem;" title="{{ $guruOverdueCount }} sesi KBM belum diisi jurnalnya">
                                {{ $guruOverdueCount }} Terlambat
                            </span>
                        @endif
                    </a>

                @elseif($role == 3)
                    <!-- Role: Wali Kelas (Dual Role: Wali Kelas & Guru Pengampu) -->
                    <div class="sidebar-section-label">Aktivitas Mengajar</div>
                    <a class="sidebar-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}" href="{{ route('guru.dashboard') }}">
                        <i class="fa-solid fa-gauge-high nav-icon"></i>
                        <span class="nav-text">Dashboard Guru</span>
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('jadwal.*') ? 'active' : '' }}" href="{{ route('jadwal.index') }}">
                        <i class="fa-regular fa-calendar-days nav-icon"></i>
                        <span class="nav-text">Jadwal Pelajaran</span>
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('jurnal.*') ? 'active' : '' }}" href="{{ route('jurnal.index') }}">
                        <i class="fa-solid fa-book-bookmark nav-icon"></i>
                        <span class="nav-text">Jurnal Mengajar</span>
                        @if($guruOverdueCount > 0)
                            <span class="badge rounded-pill bg-danger ms-auto" style="font-size: 0.68rem;" title="{{ $guruOverdueCount }} sesi KBM belum diisi jurnalnya">
                                {{ $guruOverdueCount }} Terlambat
                            </span>
                        @endif
                    </a>

                    <div class="sidebar-section-label">Tugas Wali Kelas</div>
                    <a class="sidebar-link {{ request()->routeIs('kesiswaan.izin.*') ? 'active' : '' }}" href="{{ route('kesiswaan.izin.index') }}">
                        <i class="fa-solid fa-clipboard-list nav-icon"></i>
                        <span class="nav-text">Verifikasi Izin</span>
                        @if($pendingIzinCount > 0)
                            <span class="badge rounded-pill bg-danger ms-auto px-2 py-1" style="font-size: 0.68rem;" title="{{ $pendingIzinCount }} permohonan menunggu verifikasi">
                                {{ $pendingIzinCount }} Menunggu
                            </span>
                        @endif
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('rekap.absensi.*') ? 'active' : '' }}" href="{{ route('rekap.absensi.index') }}">
                        <i class="fa-solid fa-user-xmark nav-icon"></i>
                        <span class="nav-text">Rekap Absensi Siswa</span>
                    </a>

                @elseif($role == 7)
                    <!-- Role: Guru BK -->
                    <div class="sidebar-section-label">Layanan BK & Kesiswaan</div>
                    <a class="sidebar-link {{ request()->routeIs('kesiswaan.izin.*') ? 'active' : '' }}" href="{{ route('kesiswaan.izin.index') }}">
                        <i class="fa-solid fa-clipboard-list nav-icon"></i>
                        <span class="nav-text">Verifikasi Izin</span>
                        @if($pendingIzinCount > 0)
                            <span class="badge rounded-pill bg-danger ms-auto px-2 py-1" style="font-size: 0.68rem;" title="{{ $pendingIzinCount }} permohonan menunggu verifikasi">
                                {{ $pendingIzinCount }} Menunggu
                            </span>
                        @endif
                    </a>
                    <a class="sidebar-link {{ request()->routeIs('rekap.absensi.*') ? 'active' : '' }}" href="{{ route('rekap.absensi.index') }}">
                        <i class="fa-solid fa-user-xmark nav-icon"></i>
                        <span class="nav-text">Rekap Absensi Siswa</span>
                    </a>

                @elseif($role == 5)
                    <!-- Role: Siswa -->
                    <div class="sidebar-section-label">Layanan Siswa</div>
                    <a class="sidebar-link {{ request()->routeIs('siswa.izin.*') ? 'active' : '' }}" href="{{ route('siswa.izin.index') }}">
                        <i class="fa-solid fa-file-signature nav-icon"></i>
                        <span class="nav-text">Pengajuan Izin</span>
                        @if($siswaPendingCount > 0)
                            <span class="badge rounded-pill bg-warning text-dark ms-auto px-2 py-1" style="font-size: 0.68rem;" title="{{ $siswaPendingCount }} pengajuan dalam antrean">
                                {{ $siswaPendingCount }} Diproses
                            </span>
                        @endif
                    </a>
                @endif

                <!-- Menu Akun (semua role) -->
                <div class="sidebar-section-label">Akun</div>
                <a class="sidebar-link {{ request()->routeIs('profile.index') ? 'active' : '' }}" href="{{ route('profile.index') }}">
                    <i class="fa-regular fa-id-badge nav-icon"></i>
                    <span class="nav-text">Profil Saya</span>
                </a>
            </nav>

            <!-- Profil Pengguna & Logout di Bagian Bawah Sidebar -->
            <div class="sidebar-user">
                <a href="{{ route('profile.index') }}" class="sidebar-user-avatar text-decoration-none overflow-hidden" title="Profil Saya">
                    @if(!empty($authUserFotoUrl))
                        <img src="{{ $authUserFotoUrl }}" alt="Foto" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <i class="fa-solid fa-user"></i>
                    @endif
                </a>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name" title="{{ session('nama_lengkap') ?? session('username') ?? 'Pengguna' }}">
                        {{ session('nama_lengkap') ?? session('username') ?? 'Pengguna' }}
                    </span>
                    <span class="sidebar-user-role">{{ $roleName }}</span>
                </div>
                <div class="sidebar-user-actions d-flex align-items-center gap-1">
                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="sidebar-logout-btn" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')" title="Keluar dari akun">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- ==================== AREA UTAMA (TOPBAR & KONTEN) ==================== -->
        <div class="app-main">
            <!-- Topbar Sticky -->
            <header class="app-topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle Sidebar Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="topbar-breadcrumb">
                        <span class="bc-prefix text-muted"><i class="fa-solid fa-school text-primary me-1"></i> UKK SCP</span>
                        <i class="bc-sep fa-solid fa-chevron-right text-muted opacity-50" style="font-size: 0.65rem;"></i>
                        <span class="bc-current">
                            @yield('title', 'Sistem KBM')
                        </span>
                    </div>
                </div>

                <div class="topbar-right">
                    @if(in_array($role, [3, 4, 7]) && $pendingIzinCount > 0)
                        <a href="{{ route('kesiswaan.izin.index') }}" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 py-1 px-2 text-decoration-none shadow-xs" title="{{ $pendingIzinCount }} permohonan izin menunggu verifikasi">
                            <i class="fa-solid fa-bell text-warning"></i>
                            <span class="badge bg-danger rounded-pill">{{ $pendingIzinCount }}</span>
                            <span class="d-none d-md-inline small fw-semibold text-dark">Izin Baru</span>
                        </a>
                    @endif
                    
                    @if(in_array($role, [2, 3]) && $guruOverdueCount > 0)
                        <a href="{{ route('jurnal.index') }}" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 py-1 px-2 text-decoration-none shadow-xs" title="{{ $guruOverdueCount }} sesi jurnal terlambat diisi">
                            <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                            <span class="badge bg-danger rounded-pill">{{ $guruOverdueCount }}</span>
                            <span class="d-none d-md-inline small fw-semibold text-dark">Jurnal Terlambat</span>
                        </a>
                    @endif

                    <div class="topbar-date-badge">
                        <i class="fa-regular fa-calendar text-primary"></i>
                        <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d M Y') }}</span>
                    </div>
                    <a href="{{ route('profile.index') }}" class="topbar-user-pill d-none d-sm-flex align-items-center gap-2 text-decoration-none" title="Profil Saya">
                        @if(!empty($authUserFotoUrl))
                            <img src="{{ $authUserFotoUrl }}" alt="Foto" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid #cbd5e1;">
                        @endif
                        <span class="badge-role">{{ $roleName }}</span>
                    </a>
                </div>
            </header>

            <!-- Sub Header / Judul Halaman -->
            @hasSection('page-header')
                <div class="page-header-wrapper">
                    @yield('page-header')
                </div>
            @endif

            <!-- Konten Utama -->
            <main class="app-content">
                <!-- Notifikasi Flash Global -->
                @if(session('success'))
                    <div class="app-alert app-alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-check fs-5 mt-1 text-success"></i>
                        <div class="flex-grow-1">
                            <strong>Berhasil!</strong> {{ session('success') }}
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="app-alert app-alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation fs-5 mt-1 text-danger"></i>
                        <div class="flex-grow-1">
                            <strong>Perhatian!</strong> {{ session('error') }}
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="app-alert app-alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-triangle-exclamation fs-5 mt-1 text-danger"></i>
                        <div class="flex-grow-1">
                            <strong>Terdapat beberapa kendala:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="app-footer">
                <span>&copy; {{ date('Y') }} <strong>UKK SCP</strong> &mdash; Sistem Manajemen KBM, Jadwal, & Perizinan</span>
                <span>SMK Negeri 1 Bantul &bull; Versi 1.0</span>
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Responsive Drawer Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('appSidebar');
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const closeBtn = document.getElementById('sidebarCloseBtn');
            const overlay = document.getElementById('sidebarOverlay');

            function openSidebar() {
                sidebar?.classList.add('sidebar-open');
                overlay?.classList.add('overlay-visible');
                document.body.classList.add('sidebar-mobile-open');
            }

            function closeSidebar() {
                sidebar?.classList.remove('sidebar-open');
                overlay?.classList.remove('overlay-visible');
                document.body.classList.remove('sidebar-mobile-open');
            }

            toggleBtn?.addEventListener('click', function (e) {
                e.preventDefault();
                if (sidebar?.classList.contains('sidebar-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            closeBtn?.addEventListener('click', function (e) {
                e.preventDefault();
                closeSidebar();
            });

            overlay?.addEventListener('click', function () {
                closeSidebar();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
        });
    </script>

    {{-- MODAL GANTI PASSWORD DENGAN VERIFIKASI OTP --}}
    <div class="modal fade" id="modalGantiPasswordOtp" tabindex="-1" aria-labelledby="modalGantiPasswordOtpLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius); overflow: hidden;">
                <div class="modal-header py-3 px-4 border-bottom bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white p-2 rounded-3 shadow-xs">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        <div>
                            <h5 class="modal-title fw-bold fs-6 mb-0 text-dark" id="modalGantiPasswordOtpLabel">Ganti Kata Sandi Akun</h5>
                            <small class="text-muted" style="font-size: 0.72rem;">Verifikasi keamanan OTP via email</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Alert Status --}}
                    <div id="modalOtpAlert" class="alert d-none py-2 px-3 small mb-3 border"></div>

                    <form id="formGantiPasswordOtp">
                        @csrf

                        {{-- Kotak Email & Tombol Request OTP --}}
                        <div class="mb-3 p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <label class="form-label small fw-bold text-dark mb-1 d-block">
                                <i class="fa-regular fa-envelope text-primary me-1"></i> Email Terdaftar
                            </label>
                            @if(!empty($authUserEmail))
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span class="fw-bold text-dark font-monospace" id="displayMaskedEmail">{{ $maskedUserEmail }}</span>
                                    <input type="hidden" id="modalUserEmail" name="email" value="{{ $authUserEmail }}">
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-3 fw-semibold shadow-xs" id="btnKirimOtpModal">
                                        <i class="fa-solid fa-paper-plane me-1"></i> Kirim OTP
                                    </button>
                                </div>
                            @else
                                <div class="input-group input-group-sm mb-1">
                                    <input type="email" id="modalUserEmail" name="email" class="form-control" placeholder="Contoh: nama@email.com" required>
                                    <button type="button" class="btn btn-primary fw-semibold" id="btnKirimOtpModal">
                                        <i class="fa-solid fa-paper-plane me-1"></i> Simpan & Kirim OTP
                                    </button>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Email belum tersimpan. Masukkan email aktif Anda untuk menerima kode OTP.</small>
                            @endif
                        </div>

                        {{-- Area Form Setelah OTP Dikirim --}}
                        <div id="sectionNewPasswordFields" class="d-none">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark" for="modal_otp_code">
                                    <i class="fa-solid fa-shield-halved text-primary me-1"></i> Masukkan 6 Digit Kode OTP <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="modal_otp_code" name="otp_code" class="form-control text-center font-monospace fw-bold" placeholder="______" maxlength="6" style="letter-spacing: 0.35rem; font-size: 1.25rem;" required>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-regular fa-clock me-1"></i> Berlaku 5 menit</small>
                                    <small id="otpCountdownTimer" class="text-primary font-monospace fw-semibold" style="font-size: 0.75rem;"></small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark" for="modal_new_password">
                                    Kata Sandi Baru <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" id="modal_new_password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                                    <button type="button" class="btn btn-outline-secondary px-2" onclick="toggleModalPassword('modal_new_password', this)">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-dark" for="modal_new_password_confirmation">
                                    Konfirmasi Kata Sandi Baru <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" id="modal_new_password_confirmation" name="new_password_confirmation" class="form-control" placeholder="Ulangi kata sandi baru" required>
                                    <button type="button" class="btn btn-outline-secondary px-2" onclick="toggleModalPassword('modal_new_password_confirmation', this)">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                                <div id="modalPwdMatchFeedback" class="small mt-1 d-none"></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" id="btnSubmitGantiPassword">
                                <i class="fa-solid fa-check me-1"></i> Simpan Password Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Script Manajemen Ganti Password dengan OTP di Modal
        function toggleModalPassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                icon?.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon?.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const btnKirimOtp = document.getElementById('btnKirimOtpModal');
            const emailInput = document.getElementById('modalUserEmail');
            const alertBox = document.getElementById('modalOtpAlert');
            const sectionFields = document.getElementById('sectionNewPasswordFields');
            const formGantiPwd = document.getElementById('formGantiPasswordOtp');
            const submitBtn = document.getElementById('btnSubmitGantiPassword');
            const timerEl = document.getElementById('otpCountdownTimer');
            const pwdInput = document.getElementById('modal_new_password');
            const pwdConfirm = document.getElementById('modal_new_password_confirmation');
            const pwdMatchMsg = document.getElementById('modalPwdMatchFeedback');
            let countdownInterval = null;

            function showAlert(type, msg) {
                if (!alertBox) return;
                alertBox.className = 'alert py-2 px-3 small mb-3 border ' + (type === 'success' ? 'alert-success border-success text-success' : 'alert-danger border-danger text-danger');
                alertBox.innerHTML = (type === 'success' ? '<i class="fa-solid fa-circle-check me-1"></i> ' : '<i class="fa-solid fa-circle-exclamation me-1"></i> ') + msg;
                alertBox.classList.remove('d-none');
            }

            function startTimer(seconds) {
                clearInterval(countdownInterval);
                let remaining = seconds;
                if (btnKirimOtp) btnKirimOtp.disabled = true;
                
                countdownInterval = setInterval(function() {
                    remaining--;
                    if (timerEl) timerEl.textContent = 'Kirim ulang dalam (' + remaining + 's)';
                    if (remaining <= 0) {
                        clearInterval(countdownInterval);
                        if (btnKirimOtp) {
                            btnKirimOtp.disabled = false;
                            btnKirimOtp.innerHTML = '<i class="fa-solid fa-rotate-right me-1"></i> Kirim Ulang OTP';
                        }
                        if (timerEl) timerEl.textContent = '';
                    }
                }, 1000);
            }

            btnKirimOtp?.addEventListener('click', function() {
                const email = emailInput ? emailInput.value.trim() : '';
                if (!email) {
                    showAlert('danger', 'Email wajib diisi untuk pengiriman kode OTP.');
                    return;
                }

                btnKirimOtp.disabled = true;
                btnKirimOtp.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...';

                fetch('{{ route("profile.send-otp") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ email: email })
                })
                .then(async res => {
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Respon server bukan format JSON yang valid: ' + text.substring(0, 100));
                    }
                })
                .then(data => {
                    if (data.success) {
                        let successHtml = `<div>${data.message}</div>`;
                        if (data.simulation_code) {
                            successHtml += `
                                <div class="mt-2 p-2 rounded bg-light border text-dark text-center small">
                                    <strong>Kode OTP Anda:</strong> <span class="badge bg-primary fs-6 px-3 py-1 font-monospace">${data.simulation_code}</span>
                                </div>
                            `;
                        }
                        showAlert('success', successHtml);
                        if (sectionFields) sectionFields.classList.remove('d-none');
                        startTimer(60);
                    } else {
                        showAlert('danger', data.message || 'Gagal mengirimkan kode OTP.');
                        btnKirimOtp.disabled = false;
                        btnKirimOtp.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Kirim OTP';
                    }
                })
                .catch(err => {
                    showAlert('danger', 'Terjadi kesalahan sistem saat mengirim OTP: ' + err.message);
                    btnKirimOtp.disabled = false;
                    btnKirimOtp.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Kirim OTP';
                });
            });

            // Validasi kecocokan password realtime
            function checkPasswordMatch() {
                if (!pwdConfirm || !pwdInput || !pwdConfirm.value) {
                    if (pwdMatchMsg) pwdMatchMsg.classList.add('d-none');
                    return;
                }
                if (pwdMatchMsg) pwdMatchMsg.classList.remove('d-none');
                if (pwdInput.value === pwdConfirm.value) {
                    pwdMatchMsg.innerHTML = '<span class="text-success"><i class="fa-solid fa-check me-1"></i> Kata sandi cocok!</span>';
                    if (submitBtn) submitBtn.disabled = false;
                } else {
                    pwdMatchMsg.innerHTML = '<span class="text-danger"><i class="fa-solid fa-xmark me-1"></i> Kata sandi tidak cocok.</span>';
                    if (submitBtn) submitBtn.disabled = true;
                }
            }
            pwdInput?.addEventListener('input', checkPasswordMatch);
            pwdConfirm?.addEventListener('input', checkPasswordMatch);

            // Filter hanya angka pada input OTP
            const otpCodeInput = document.getElementById('modal_otp_code');
            otpCodeInput?.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Submit ganti password dengan OTP
            formGantiPwd?.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!pwdInput || pwdInput.value.length < 6) {
                    showAlert('danger', 'Kata sandi baru minimal 6 karakter.');
                    return;
                }

                if (pwdInput.value !== pwdConfirm.value) {
                    showAlert('danger', 'Konfirmasi kata sandi baru tidak sesuai.');
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan Password...';
                }

                const otpCode = otpCodeInput ? otpCodeInput.value.trim() : '';

                fetch('{{ route("profile.update-password") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        otp_code: otpCode,
                        new_password: pwdInput.value,
                        new_password_confirmation: pwdConfirm.value
                    })
                })
                .then(async res => {
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Respon server bukan format JSON yang valid: ' + text.substring(0, 100));
                    }
                })
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        if (submitBtn) {
                            submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Berhasil Diperbarui';
                        }
                        setTimeout(() => {
                            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('modalGantiPasswordOtp'));
                            if (modalInstance) modalInstance.hide();
                            window.location.reload();
                        }, 1400);
                    } else {
                        showAlert('danger', data.message || 'Gagal mengubah kata sandi. Periksa kembali kode OTP Anda.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Simpan Password Baru';
                        }
                    }
                })
                .catch(err => {
                    showAlert('danger', 'Terjadi kendala: ' + err.message);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Simpan Password Baru';
                    }
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
