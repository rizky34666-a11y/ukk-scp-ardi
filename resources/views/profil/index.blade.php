@extends('layouts.app')

@section('title', 'Profil Saya - UKK SCP')

@section('page-header')
<div>
    <h1 class="page-title">Profil Saya</h1>
    <p class="page-subtitle">Kelola foto profil, email, nomor WhatsApp, dan keamanan akun Anda</p>
</div>
@endsection

@push('styles')
<style>
    .profil-avatar {
        width: 128px; height: 128px; border-radius: 50%;
        object-fit: cover; border: 4px solid #e2e8f0;
        background: linear-gradient(135deg, #3aa8f8, #1560c4);
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-size: 2.6rem; font-weight: 700; overflow: hidden; margin: 0 auto;
    }
    .profil-avatar img { width: 100%; height: 100%; object-fit: cover; }
</style>
@endpush

@section('content')
@php
    $inisial = strtoupper(mb_substr(trim($user->nama_lengkap ?: $user->username), 0, 1));
@endphp

<div class="row g-4">
    {{-- ===== KOLOM KIRI: FOTO PROFIL ===== --}}
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-body text-center">
                <div class="profil-avatar mb-3" id="avatarBox">
                    @if($fotoUrl)
                        <img src="{{ $fotoUrl }}" alt="Foto Profil" id="avatarImg">
                    @else
                        <span id="avatarInisial">{{ $inisial }}</span>
                    @endif
                </div>
                <h5 class="fw-bold mb-0">{{ $user->nama_lengkap }}</h5>
                <div class="text-muted small mb-3">{{ $roleName ?? '' }}</div>

                <label for="foto" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-camera me-1"></i> Pilih Foto
                </label>
                @if($fotoUrl)
                    <button type="submit" form="formHapusFoto" class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Hapus foto profil Anda?')">
                        <i class="fa-regular fa-trash-can me-1"></i> Hapus
                    </button>
                @endif
                <div class="form-text mt-2">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                @error('foto')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- ===== KOLOM KANAN: DATA PROFIL ===== --}}
    <div class="col-lg-8">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="formProfil">
            @csrf
            <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp" class="d-none">

            <div class="app-card mb-4">
                <div class="app-card-header">
                    <h5 class="app-card-title mb-0"><i class="fa-regular fa-id-card"></i> Data Akun</h5>
                </div>
                <div class="app-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" value="{{ $user->nama_lengkap }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ $labelId }}</label>
                            <input type="text" class="form-control font-monospace" value="{{ $user->username }}" disabled>
                        </div>
                        <div class="col-12">
                            <div class="form-text mt-0">
                                <i class="fa-solid fa-lock me-1"></i>
                                Nama lengkap dan {{ $labelId }} hanya dapat diubah oleh Waka Kurikulum
                                @if(session('id_role') == 4) (melalui menu <a href="{{ route('akun.index') }}">Manajemen Akun</a>)@endif.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}" required
                                       data-original="{{ $user->email }}">
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Dipakai untuk menerima kode OTP.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="no_hp">Nomor WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-brands fa-whatsapp"></i></span>
                                <input type="text" id="no_hp" name="no_hp"
                                       class="form-control @error('no_hp') is-invalid @enderror"
                                       value="{{ old('no_hp', $user->no_hp) }}" placeholder="08123456789">
                            </div>
                            @error('no_hp')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 {{ $errors->has('current_password') ? '' : 'd-none' }}" id="boxPasswordSaatIni">
                            <label class="form-label" for="current_password">Password Saat Ini <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" id="current_password" name="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       autocomplete="current-password">
                            </div>
                            @error('current_password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Wajib diisi karena Anda mengubah email.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>

        {{-- ===== KEAMANAN: RESET PASSWORD ===== --}}
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title mb-0"><i class="fa-solid fa-shield-halved"></i> Keamanan</h5>
            </div>
            <div class="app-card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="fw-semibold">Reset / Ganti Password</div>
                    <div class="small text-muted">Kode OTP dikirim ke email {{ \App\Services\OtpService::maskEmail($user->email) }}.</div>
                </div>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalGantiPasswordOtp">
                    <i class="fa-solid fa-key me-1"></i> Ganti Password
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Form terpisah untuk hapus foto (tidak boleh bersarang di form lain) --}}
<form action="{{ route('profile.photo.delete') }}" method="POST" id="formHapusFoto" class="d-none">
    @csrf
</form>
@endsection

@push('scripts')
<script>
    (function () {
        const fotoInput = document.getElementById('foto');
        const avatarBox = document.getElementById('avatarBox');
        const emailInput = document.getElementById('email');
        const boxPwd = document.getElementById('boxPasswordSaatIni');
        const pwdInput = document.getElementById('current_password');

        // Pratinjau foto (baru tersimpan setelah klik "Simpan Perubahan")
        fotoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran foto maksimal 2 MB.');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = e => {
                avatarBox.innerHTML = '<img src="' + e.target.result + '" alt="Pratinjau">';
            };
            reader.readAsDataURL(file);
        });

        // Tampilkan kolom password saat email diubah
        function togglePwd() {
            const changed = emailInput.value.trim().toLowerCase() !== (emailInput.dataset.original || '').toLowerCase();
            boxPwd.classList.toggle('d-none', !changed && !boxPwd.querySelector('.is-invalid'));
            pwdInput.required = changed;
        }
        emailInput.addEventListener('input', togglePwd);
        togglePwd();
    })();
</script>
@endpush
