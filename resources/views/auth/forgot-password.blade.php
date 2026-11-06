<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password & Verifikasi OTP - UKK SCP</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui-polish.css') }}?v={{ @filemtime(public_path('css/ui-polish.css')) }}">
</head>
<body>

    @php
        $currentStep = old('step', 'identity');
    @endphp

    <div class="auth-wrapper">
        <div class="auth-card" style="max-width:480px;">

            {{-- Header --}}
            <div class="auth-header">
                <div class="auth-brand-badge">
                    <img src="{{ asset('image/logosmk.png') }}" alt="Logo SMK">
                </div>
                <h1 class="auth-title">Atur Ulang Password</h1>
                <p class="auth-subtitle">Verifikasi keamanan via kode OTP ke email terdaftar</p>
            </div>

            {{-- Flash / Error Alerts --}}
            @if(session('success'))
                <div class="app-alert app-alert-success mb-3" role="alert">
                    <i class="fa-solid fa-circle-check fs-5 mt-1 text-success"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="app-alert app-alert-danger mb-3" role="alert">
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

            {{-- INDIKATOR 3 LANGKAH --}}
            <div class="step-indicator mb-4">
                <div class="step-item {{ $currentStep == 'identity' ? 'active' : ($currentStep != 'identity' ? 'done' : '') }}">
                    <div class="step-num">
                        @if($currentStep != 'identity') <i class="fa-solid fa-check" style="font-size:.7rem;"></i> @else 1 @endif
                    </div>
                    <span>1. Data Akun</span>
                </div>
                <div class="step-line {{ in_array($currentStep, ['otp', 'reset']) ? 'active' : '' }}"></div>
                <div class="step-item {{ $currentStep == 'otp' ? 'active' : ($currentStep == 'reset' ? 'done' : '') }}">
                    <div class="step-num">
                        @if($currentStep == 'reset') <i class="fa-solid fa-check" style="font-size:.7rem;"></i> @else 2 @endif
                    </div>
                    <span>2. Kode OTP</span>
                </div>
                <div class="step-line {{ $currentStep == 'reset' ? 'active' : '' }}"></div>
                <div class="step-item {{ $currentStep == 'reset' ? 'active' : '' }}">
                    <div class="step-num">3</div>
                    <span>3. Password Baru</span>
                </div>
            </div>

            {{-- STEP 1: Verifikasi Identitas (Username + Nomor HP) --}}
            <div id="stepVerify" class="{{ $currentStep == 'identity' ? '' : 'd-none' }}">
                <div class="text-center mb-3">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
                        <i class="fa-solid fa-shield-halved me-1"></i> Langkah 1 dari 3
                    </span>
                    <p class="small text-muted mt-2 mb-0">Masukkan username dan email yang terdaftar pada akun Anda untuk menerima kode verifikasi OTP.</p>
                </div>

                <form action="{{ route('forgot-password.verify') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="fp_username">Username / NIP / NISN</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                            <input type="text" id="fp_username" name="username"
                                   class="form-control @error('username') is-invalid @enderror"
                                   placeholder="Contoh: 19850101... atau admin"
                                   value="{{ old('username') }}" required autofocus>
                        </div>
                        @error('username')
                            <div class="form-text text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="fp_email">Email Terdaftar</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                            <input type="email" id="fp_email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="Contoh: nama@email.com"
                                   value="{{ old('email') }}" required>
                        </div>
                        @error('email')
                            <div class="form-text text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>
                        @enderror
                        <div class="form-text"><i class="fa-regular fa-envelope text-primary me-1"></i>Kode OTP 6 digit akan dikirimkan ke email ini.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        <i class="fa-solid fa-paper-plane me-1"></i> Kirim Kode OTP
                    </button>
                </form>
            </div>

            {{-- STEP 2: Verifikasi Kode OTP (6 Digit) --}}
            <div id="stepOtp" class="{{ $currentStep == 'otp' ? '' : 'd-none' }}">
                <div class="text-center mb-3">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1">
                        <i class="fa-solid fa-key me-1"></i> Langkah 2 dari 3
                    </span>
                    <h6 class="fw-bold mt-2 mb-1 text-dark">Masukkan Kode OTP</h6>
                    <p class="small text-muted mb-0">
                        Kode 6 digit dikirimkan ke email: 
                        <strong class="text-primary">{{ old('masked_email', 'email terdaftar') }}</strong>
                    </p>
                    <p class="small text-muted mb-0">Tidak ada di kotak masuk? Periksa juga folder spam.</p>
                    @if(old('otp_simulation') && config('app.debug'))
                        <div class="alert alert-info py-2 px-3 mt-2 text-start small border border-info">
                            <i class="fa-solid fa-circle-info me-1"></i> <strong>Mode Uji Coba:</strong> Kode OTP Anda adalah: <strong class="fs-6 text-dark font-monospace">{{ old('otp_simulation') }}</strong>
                        </div>
                    @endif
                </div>

                <form action="{{ route('forgot-password.verify-otp') }}" method="POST">
                    @csrf
                    <input type="hidden" name="username" value="{{ old('verified_username') }}">
                    <input type="hidden" name="masked_email" value="{{ old('masked_email') }}">

                    <div class="mb-4">
                        <label class="form-label text-center d-block fw-semibold" for="otp_code">Kode OTP (6 Digit)</label>
                        <div class="d-flex justify-content-center">
                            <input type="text" id="otp_code" name="otp_code"
                                   class="form-control form-control-lg text-center font-monospace fw-bold @error('otp_code') is-invalid @enderror"
                                   placeholder="______"
                                   maxlength="6"
                                   style="letter-spacing: 0.5rem; font-size: 1.5rem; max-width: 240px;"
                                   autocomplete="one-time-code" required autofocus>
                        </div>
                        @error('otp_code')
                            <div class="form-text text-danger text-center mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>
                        @enderror
                        <div class="form-text text-center mt-1">
                            <i class="fa-regular fa-clock me-1"></i> Kode berlaku selama 5 menit.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        <i class="fa-solid fa-shield-check me-1"></i> Verifikasi OTP & Lanjutkan
                    </button>
                </form>

                {{-- Kirim Ulang OTP --}}
                <div class="text-center pt-2">
                    <form action="{{ route('forgot-password.resend-otp') }}" method="POST" id="formResendOtp" class="d-inline">
                        @csrf
                        <input type="hidden" name="username" value="{{ old('verified_username') }}">
                        <button type="submit" class="btn btn-link text-decoration-none small text-muted p-0" id="btnResendOtp">
                            <i class="fa-solid fa-rotate-right me-1"></i> Belum menerima kode? Kirim Ulang OTP
                        </button>
                    </form>
                </div>
            </div>

            {{-- STEP 3: Reset Password (setelah verifikasi OTP berhasil) --}}
            <div id="stepReset" class="{{ $currentStep == 'reset' ? '' : 'd-none' }}">
                <div class="text-center mb-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                        <i class="fa-solid fa-circle-check me-1"></i> Langkah 3 dari 3
                    </span>
                    <h6 class="fw-bold mt-2 mb-1 text-dark">Buat Kata Sandi Baru</h6>
                    <p class="small text-muted mb-0">Akun: <strong class="text-primary">{{ old('verified_username') }}</strong> telah terverifikasi aman.</p>
                </div>

                <form action="{{ route('forgot-password.reset') }}" method="POST">
                    @csrf
                    <input type="hidden" name="username" value="{{ old('verified_username') }}">

                    <div class="mb-3">
                        <label class="form-label" for="new_password">Password Baru</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" id="new_password" name="new_password"
                                   class="form-control @error('new_password') is-invalid @enderror"
                                   placeholder="Minimal 6 karakter" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary px-3" id="toggleNewPwd" title="Tampilkan / Sembunyikan">
                                <i class="fa-regular fa-eye" id="iconNewPwd"></i>
                            </button>
                        </div>
                        @error('new_password')
                            <div class="form-text text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="new_password_confirmation">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock-open"></i></span>
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                                   class="form-control"
                                   placeholder="Ulangi password baru" required>
                            <button type="button" class="btn btn-outline-secondary px-3" id="toggleConfirmPwd" title="Tampilkan / Sembunyikan">
                                <i class="fa-regular fa-eye" id="iconConfirmPwd"></i>
                            </button>
                        </div>
                        <div id="pwdMatchMsg" class="form-text mt-1 d-none"></div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3" id="btnResetSubmit">
                        <i class="fa-solid fa-key me-1"></i> Simpan Password Baru
                    </button>
                </form>
            </div>

            {{-- Kembali ke Login --}}
            <div class="text-center pt-3 border-top">
                <p class="mb-0 text-muted small">
                    Sudah ingat kata sandi Anda?
                    <a href="{{ route('login') }}" class="fw-semibold text-decoration-none" style="color:#0369a1;">Kembali ke Login</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle show/hide password
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        document.getElementById('toggleNewPwd')?.addEventListener('click', () => togglePassword('new_password', 'iconNewPwd'));
        document.getElementById('toggleConfirmPwd')?.addEventListener('click', () => togglePassword('new_password_confirmation', 'iconConfirmPwd'));

        // Live password match validation
        const newPwd     = document.getElementById('new_password');
        const confirmPwd = document.getElementById('new_password_confirmation');
        const matchMsg   = document.getElementById('pwdMatchMsg');
        const submitBtn  = document.getElementById('btnResetSubmit');

        function checkMatch() {
            if (!confirmPwd || !newPwd || confirmPwd.value === '') {
                matchMsg && matchMsg.classList.add('d-none');
                return;
            }
            matchMsg && matchMsg.classList.remove('d-none');
            if (newPwd.value === confirmPwd.value) {
                matchMsg.innerHTML = '<i class="fa-solid fa-circle-check me-1 text-success"></i><span class="text-success">Password cocok!</span>';
                if (submitBtn) submitBtn.disabled = false;
            } else {
                matchMsg.innerHTML = '<i class="fa-solid fa-circle-xmark me-1 text-danger"></i><span class="text-danger">Password tidak cocok.</span>';
                if (submitBtn) submitBtn.disabled = true;
            }
        }
        newPwd && newPwd.addEventListener('input', checkMatch);
        confirmPwd && confirmPwd.addEventListener('input', checkMatch);

        // Hanya izinkan angka pada OTP input
        const otpInput = document.getElementById('otp_code');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    </script>

    <style>
        .step-indicator { display:flex; align-items:center; justify-content:center; }
        .step-item {
            display:flex; flex-direction:column; align-items:center;
            gap:.3rem; font-size:.72rem; font-weight:600; color:#94a3b8;
        }
        .step-item.active { color:#0369a1; }
        .step-item.done   { color:#059669; }
        .step-num {
            width:30px; height:30px; border-radius:50%;
            background:#e2e8f0; color:#64748b;
            display:flex; align-items:center; justify-content:center;
            font-size:.8rem; font-weight:700; transition:all .2s;
        }
        .step-item.active .step-num { background:#0369a1; color:#fff; box-shadow:0 0 0 4px rgba(3,105,161,.15); }
        .step-item.done   .step-num { background:#059669; color:#fff; box-shadow:0 0 0 4px rgba(5,150,105,.15); }
        .step-line { flex:1; height:2px; background:#e2e8f0; margin:0 .6rem 1.35rem; border-radius:2px; }
        .step-line.active { background:linear-gradient(90deg,#059669,#0369a1); }
    </style>
</body>
</html>
