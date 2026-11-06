<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UKK SCP</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Custom Style -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui-polish.css') }}?v={{ @filemtime(public_path('css/ui-polish.css')) }}">
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <!-- Header Logo & Title -->
            <div class="auth-header">
                <div class="auth-brand-badge">
                    <img src="{{ asset('image/logosmk.png') }}" alt="Logo SMK">
                </div>
                <h1 class="auth-title">Masuk ke Sistem</h1>
                <p class="auth-subtitle">Sistem Penjadwalan, Jurnal Guru &amp; Perizinan</p>
            </div>

            <!-- Notifikasi Flash -->
            @if(session('success'))
                <div class="app-alert app-alert-success" role="alert">
                    <i class="fa-solid fa-circle-check fs-5 mt-1 text-success"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="app-alert app-alert-danger" role="alert">
                    <i class="fa-solid fa-triangle-exclamation fs-5 mt-1 text-danger"></i>
                    <div>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Form Login -->
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="username">NISN / NIP / Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                        <input type="text" id="username" name="username" class="form-control"
                               placeholder="Masukkan NISN atau NIP Anda"
                               required value="{{ old('username') }}" autofocus>
                    </div>
                </div>

                <div class="mb-1">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label mb-0" for="password">Kata Sandi</label>
                        <a href="{{ route('forgot-password.form') }}"
                           class="text-decoration-none fw-semibold"
                           style="font-size:0.775rem; color:#44b8f8;">
                            <i class="fa-solid fa-key me-1" style="font-size:.7rem;"></i>Lupa Password?
                        </a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Masukkan kata sandi" required>
                        <button type="button" class="btn btn-outline-secondary px-3" id="togglePassword"
                                title="Tampilkan / Sembunyikan Kata Sandi">
                            <i class="fa-regular fa-eye" id="iconPassword"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4"></div>

                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk Sekarang
                </button>

                <div class="text-center pt-2 border-top">
                    <p class="mb-0 text-muted small">
                        Belum punya akun siswa?
                        <a href="{{ route('register') }}" class="fw-semibold text-decoration-none" style="color:#44b8f8;">Daftar Akun Baru</a>
                    </p>
                </div>
            </form>

            <!-- Petunjuk Pengguna -->
            <div class="mt-4 p-2 rounded text-center small" style="background:#e0f5fe;border:1px solid #bae6fd;color:#075985;">
                <i class="fa-solid fa-circle-info me-1" style="color:#44b8f8;"></i>
                Masuk menggunakan NISN (Siswa) atau NIP/Username (Guru &amp; Waka).
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Show / hide password toggle
        const toggleBtn   = document.getElementById('togglePassword');
        const pwdInput    = document.getElementById('password');
        const pwdIcon     = document.getElementById('iconPassword');

        toggleBtn.addEventListener('click', function () {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                pwdIcon.classList.replace('fa-eye', 'fa-eye-slash');
                toggleBtn.title = 'Sembunyikan Kata Sandi';
            } else {
                pwdInput.type = 'password';
                pwdIcon.classList.replace('fa-eye-slash', 'fa-eye');
                toggleBtn.title = 'Tampilkan Kata Sandi';
            }
        });
    </script>
</body>
</html>