<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - UKK SCP</title>

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
        <div class="auth-card" style="max-width: 480px;">
            <!-- Header Logo & Title -->
            <div class="auth-header">
                <div class="auth-brand-badge">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h1 class="auth-title">Pendaftaran Akun</h1>
                <p class="auth-subtitle">Buat akun siswa untuk pengajuan izin dan permohonan</p>
            </div>

            <!-- Notifikasi Error -->
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

            <!-- Form Registrasi -->
            <form action="{{ route('register.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="username">NISN / Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-id-badge"></i></span>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Nomor Induk Siswa Nasional" required value="{{ old('username') }}" autofocus>
                    </div>
                    <div class="form-text">Gunakan NISN resmi yang sudah terdaftar di sekolah.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="nama_lengkap">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" placeholder="Nama lengkap sesuai data sekolah" required value="{{ old('nama_lengkap') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Contoh: nama@email.com" required value="{{ old('email') }}">
                    </div>
                    <div class="form-text">Dipakai untuk menerima kode OTP saat lupa / ganti password.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="password">Kata Sandi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="password_confirmation">Ulangi Sandi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ketik ulang sandi" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="fa-solid fa-user-check"></i> Daftar Sekarang
                </button>

                <div class="text-center pt-2 border-top">
                    <p class="mb-0 text-muted small">
                        Sudah memiliki akun? 
                        <a href="{{ route('login') }}" class="fw-semibold text-decoration-none" style="color:#44b8f8;">Masuk di sini</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>