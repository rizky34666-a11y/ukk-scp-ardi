<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\OtpService;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = DB::table('users')->where('username', $request->username)->first();

        if ($user && Hash::check($request->password, $user->password_hash)) {
            session([
                'id_user' => $user->id_user,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'id_role' => $user->id_role
            ]);

            // REDIRECT BERDASARKAN ID_ROLE SECARA SPESIFIK
            // 4 = Waka Kurikulum
            // 3 = Wali Kelas (Dual role: KBM Guru + Verifikasi Izin)
            // 7 = Guru BK (ke verifikasi izin kesiswaan)
            // 2 = Guru Pengampu
            // 5 = Siswa
            switch ($user->id_role) {
                case 4:
                    return redirect()->route('waka.dashboard')->with('success', 'Berhasil login sebagai Waka Kurikulum!');
                case 3:
                    return redirect()->route('guru.dashboard')->with('success', 'Berhasil login sebagai Wali Kelas & Guru!');
                case 7:
                    return redirect()->route('kesiswaan.izin.index')->with('success', 'Berhasil login sebagai Guru BK!');
                case 2:
                    return redirect()->route('guru.dashboard')->with('success', 'Berhasil login!');
                case 5:
                    return redirect()->route('siswa.izin.index')->with('success', 'Berhasil login sebagai Siswa!');
                default:
                    return redirect()->route('login')->withErrors(['username' => 'Role pengguna tidak dikenali sistem.']);
            }
        }

        return back()->withErrors(['username' => 'Username atau password salah.']);
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'email' => 'required|email|max:100|unique:users,email',
        ], [
            'email.required' => 'Email wajib diisi untuk verifikasi OTP.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email sudah dipakai oleh akun lain.',
        ]);

        DB::table('users')->insert([
            'username' => $request->username,
            'nama_lengkap' => $request->nama_lengkap,
            'password_hash' => Hash::make($request->password),
            'email' => strtolower(trim($request->email)),
            'id_role' => 5, // Otomatis diatur sebagai Siswa
            'is_aktif' => 1,
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }

    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Step 1 — Verifikasi identitas (username & no. HP) lalu KIRIM OTP ke nomor terdaftar
     */
    public function verifyIdentity(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'email'    => 'required|email',
        ], [
            'username.required' => 'Username wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
        ]);

        $user = DB::table('users')
            ->where('username', $request->username)
            ->where('email', strtolower(trim($request->email)))
            ->first();

        if (!$user) {
            return back()
                ->withInput()
                ->withErrors([
                    'username' => 'Username atau email tidak cocok dengan data terdaftar di sistem. Jika email belum terdaftar, hubungi admin (Waka Kurikulum).',
                ]);
        }

        // Kirim OTP ke email terdaftar
        $otpRes = OtpService::sendOtp($user->email, $user->username, 'Lupa Password');

        if (!$otpRes['success']) {
            return back()->withInput()->withErrors(['username' => $otpRes['message']]);
        }

        return back()->withInput([
            'step'              => 'otp',
            'verified_username' => $user->username,
            'masked_email'      => $otpRes['masked_email'],
            'otp_simulation'    => $otpRes['simulation_code'],
        ])->with('success', $otpRes['message']);
    }

    /**
     * Step 2 — Verifikasi Kode OTP yang dimasukkan pengguna
     */
    public function verifyOtpCode(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'otp_code' => 'required|string|min:6|max:6',
        ], [
            'otp_code.required' => 'Kode OTP wajib diisi.',
            'otp_code.min'      => 'Kode OTP harus berupa 6 digit angka.',
            'otp_code.max'      => 'Kode OTP harus berupa 6 digit angka.',
        ]);

        $isValid = OtpService::verifyOtp($request->username, $request->otp_code);

        if (!$isValid) {
            return back()->withInput([
                'step'              => 'otp',
                'verified_username' => $request->username,
                'masked_email'      => $request->masked_email ?? '',
            ])->withErrors([
                'otp_code' => 'Kode OTP salah atau telah kedaluwarsa. Silakan masukkan kode yang benar atau kirim ulang OTP.',
            ]);
        }

        // Sukses verifikasi OTP, lanjut ke Step 3 (Reset Password)
        return back()->withInput([
            'step'              => 'reset',
            'verified_username' => $request->username,
        ])->with('success', 'Kode OTP berhasil diverifikasi! Silakan buat password baru Anda.');
    }

    /**
     * Kirim Ulang OTP untuk Lupa Password
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
        ]);

        $user = DB::table('users')->where('username', $request->username)->first();

        if (!$user || empty($user->email)) {
            return back()->withErrors(['username' => 'Pengguna tidak ditemukan atau tidak memiliki email terdaftar.']);
        }

        $otpRes = OtpService::sendOtp($user->email, $user->username, 'Lupa Password');

        $flash = [
            'step'              => 'otp',
            'verified_username' => $user->username,
            'masked_email'      => $otpRes['masked_email'],
            'otp_simulation'    => $otpRes['simulation_code'],
        ];

        if (!$otpRes['success']) {
            return back()->withInput($flash)->withErrors(['otp_code' => $otpRes['message']]);
        }

        return back()->withInput($flash)->with('success', $otpRes['message']);
    }

    /**
     * Step 3 — Simpan password baru setelah lolos verifikasi OTP
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'username'                  => 'required|string|exists:users,username',
            'new_password'              => 'required|string|min:6',
            'new_password_confirmation' => 'required|string|same:new_password',
        ], [
            'new_password.min'               => 'Password minimal 6 karakter.',
            'new_password_confirmation.same' => 'Konfirmasi password tidak cocok.',
            'username.exists'                => 'Sesi tidak valid. Silakan ulangi proses.',
        ]);

        // Pengamanan OTP: Pastikan pengguna benar-benar telah memverifikasi OTP
        if (!OtpService::isVerified($request->username)) {
            return redirect()->route('forgot-password.form')->withErrors([
                'username' => 'Sesi verifikasi OTP telah berakhir atau tidak sah. Silakan ulangi langkah verifikasi.',
            ]);
        }

        DB::table('users')
            ->where('username', $request->username)
            ->update(['password_hash' => Hash::make($request->new_password)]);

        // Bersihkan status verifikasi
        OtpService::clearVerification($request->username);

        return redirect()->route('login')
            ->with('success', 'Password berhasil diperbarui! Silakan login dengan password baru Anda.');
    }

    /**
     * AJAX: Kirim OTP untuk Pengguna yang Sedang Login (Ganti Password di Modal Profil)
     */
    public function sendProfileOtp(Request $request)
    {
        $idUser = session('id_user') ?? auth()->id();
        if (!$idUser) {
            return response()->json(['success' => false, 'message' => 'Sesi login tidak valid.'], 401);
        }

        $user = DB::table('users')->where('id_user', $idUser)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        $email = $user->email;

        // Jika email pada database masih kosong, izinkan input email baru
        if (empty($email)) {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'email' => 'required|email|max:100|unique:users,email',
            ], [
                'email.required' => 'Email wajib diisi untuk pengiriman kode OTP.',
                'email.email'    => 'Format email tidak valid.',
                'email.unique'   => 'Email sudah dipakai oleh akun lain.',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $email = strtolower(trim($request->email));
            DB::table('users')->where('id_user', $idUser)->update(['email' => $email]);
        }

        $otpRes = OtpService::sendOtp($email, $user->username, 'Ganti Password');

        return response()->json([
            'success'         => $otpRes['success'],
            'message'         => $otpRes['message'],
            'masked_email'    => $otpRes['masked_email'],
            'simulation_code' => $otpRes['simulation_code'],
        ], $otpRes['success'] ? 200 : 422);
    }

    /**
     * POST: Ganti Password Pengguna yang Sedang Login dengan Validasi OTP
     */
    public function updateProfilePassword(Request $request)
    {
        $idUser = session('id_user') ?? auth()->id();
        $isJsonRequest = $request->expectsJson() || $request->ajax() || $request->isJson() || $request->wantsJson() || ($request->header('X-Requested-With') === 'XMLHttpRequest');

        if (!$idUser) {
            if ($isJsonRequest) {
                return response()->json(['success' => false, 'message' => 'Sesi login telah habis. Silakan refresh halaman dan login kembali.'], 401);
            }
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $user = DB::table('users')->where('id_user', $idUser)->first();
        if (!$user) {
            if ($isJsonRequest) {
                return response()->json(['success' => false, 'message' => 'Data pengguna tidak ditemukan di sistem.'], 404);
            }
            return redirect()->back()->withErrors(['error' => 'Pengguna tidak ditemukan.']);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'otp_code'                  => 'required|string|min:6|max:6',
            'new_password'              => 'required|string|min:6',
            'new_password_confirmation' => 'required|string|same:new_password',
        ], [
            'otp_code.required'              => 'Kode OTP wajib diisi.',
            'otp_code.min'                   => 'Kode OTP harus 6 digit angka.',
            'otp_code.max'                   => 'Kode OTP harus 6 digit angka.',
            'new_password.min'               => 'Password baru minimal 6 karakter.',
            'new_password_confirmation.same' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if ($validator->fails()) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $isValidOtp = OtpService::verifyOtp($user->username, $request->otp_code);

        if (!$isValidOtp) {
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP salah atau telah kedaluwarsa. Silakan periksa kembali atau kirim ulang OTP.'
                ], 422);
            }
            return redirect()->back()->withErrors(['otp_code' => 'Kode OTP salah atau telah kedaluwarsa. Silakan periksa kembali atau kirim ulang OTP.']);
        }

        DB::table('users')->where('id_user', $idUser)->update([
            'password_hash' => Hash::make($request->new_password),
        ]);

        OtpService::clearVerification($user->username);

        if ($isJsonRequest) {
            return response()->json([
                'success' => true,
                'message' => 'Kata sandi akun Anda berhasil diperbarui dengan verifikasi OTP!',
            ]);
        }

        return redirect()->back()->with('success', 'Kata sandi berhasil diperbarui dengan verifikasi OTP!');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login')->with('success', 'Berhasil logout!');
    }
}
