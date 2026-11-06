<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfilController extends Controller
{
    private const FOTO_DIR = 'uploads/profil';

    private function currentUser()
    {
        $idUser = session('id_user');

        return $idUser ? DB::table('users')->where('id_user', $idUser)->first() : null;
    }

    public function index()
    {
        $user = $this->currentUser();
        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $labelId = match ((int) $user->id_role) {
            5       => 'NISN',
            2, 3, 7 => 'NIP',
            default => 'Username',
        };

        $roleName = match ((int) $user->id_role) {
            4       => 'Waka Kurikulum',
            3       => 'Wali Kelas & Guru',
            2       => 'Guru Pengampu',
            7       => 'Guru BK',
            5       => 'Siswa',
            default => 'Pengguna',
        };

        return view('profil.index', [
            'user'     => $user,
            'roleName' => $roleName,
            'labelId' => $labelId,
            'fotoUrl' => $this->fotoUrl($user->foto_profil ?? null),
        ]);
    }

    /**
     * Simpan perubahan profil: email, nomor WhatsApp, dan foto profil.
     * Nama lengkap & NIP/NISN (username) TIDAK dapat diubah dari sini (hanya Waka via Manajemen Akun).
     */
    public function update(Request $request)
    {
        $user = $this->currentUser();
        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        // Normalisasi nomor WA: sisakan angka dan tanda + saja
        if ($request->filled('no_hp')) {
            $request->merge(['no_hp' => preg_replace('/[^0-9+]/', '', $request->no_hp)]);
        }

        $validator = Validator::make($request->all(), [
            'email'            => 'required|email|max:100|unique:users,email,' . $user->id_user . ',id_user',
            'no_hp'            => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'foto'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'current_password' => 'nullable|string',
        ], [
            'email.required' => 'Email wajib diisi (dipakai untuk kode OTP).',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email sudah dipakai oleh akun lain.',
            'no_hp.regex'    => 'Nomor WhatsApp tidak valid. Contoh: 08123456789 atau +628123456789.',
            'foto.image'     => 'File foto harus berupa gambar.',
            'foto.mimes'     => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.max'       => 'Ukuran foto maksimal 2 MB.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $newEmail = strtolower(trim($request->email));

        // Mengganti email wajib konfirmasi password saat ini (email dipakai untuk reset password via OTP)
        if (strcasecmp((string) $user->email, $newEmail) !== 0) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password_hash)) {
                return back()
                    ->withErrors(['current_password' => 'Untuk mengubah email, masukkan password Anda saat ini dengan benar.'])
                    ->withInput();
            }
        }

        $data = [
            'email' => $newEmail,
            'no_hp' => $request->filled('no_hp') ? $request->no_hp : null,
        ];

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $dir  = public_path(self::FOTO_DIR);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $filename = 'u' . $user->id_user . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file->extension();
            $file->move($dir, $filename);

            $this->deleteFoto($user->foto_profil ?? null);
            $data['foto_profil'] = $filename;
        }

        DB::table('users')->where('id_user', $user->id_user)->update($data);

        return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui.');
    }

    public function hapusFoto()
    {
        $user = $this->currentUser();
        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $this->deleteFoto($user->foto_profil ?? null);
        DB::table('users')->where('id_user', $user->id_user)->update(['foto_profil' => null]);

        return redirect()->route('profile.index')->with('success', 'Foto profil dihapus.');
    }

    private function fotoUrl(?string $filename): ?string
    {
        if (empty($filename)) {
            return null;
        }

        $path = self::FOTO_DIR . '/' . basename($filename);

        return File::exists(public_path($path)) ? asset($path) : null;
    }

    private function deleteFoto(?string $filename): void
    {
        if (empty($filename)) {
            return;
        }

        $path = public_path(self::FOTO_DIR . '/' . basename($filename));
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
