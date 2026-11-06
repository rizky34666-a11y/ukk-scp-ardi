<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JadwalPelajaran;
use App\Models\SystemConfiguration;
use App\Models\Journal;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Guru;
use App\Services\ExcelImportService;
use Exception;

class JadwalPelajaranController extends Controller
{
    public function index()
    {
        // PENGAMAN ANTI-BOBOL: Pastikan user sudah login
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $idUser = session('id_user');
        $idRole = session('id_role'); 
        $username = session('username'); 

        $guruLogin = null;
        
        // Cari guru berdasarkan NIP
        $guruLogin = Guru::where('nip', $username)->first();

        // Jika Guru atau Wali Kelas (role 2 / 3) belum terdaftar di tabel guru, sinkronkan otomatis
        if (!$guruLogin && in_array($idRole, [2, 3])) {
            $nama = session('nama_lengkap') ?? $username;
            $guruLogin = Guru::where('nama_guru', $nama)->first();
            if (!$guruLogin) {
                $guruLogin = Guru::create([
                    'nip' => $username,
                    'nama_guru' => $nama,
                ]);
            }
        }

        // Ambil data pendukung
        $tahunAjaran = DB::table('tahun_ajaran')->get();
        $kelas = DB::table('kelas')
            ->leftJoin('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
            ->select('kelas.*', 'jurusan.nama_jurusan')
            ->orderBy('kelas.tingkat')
            ->orderBy('jurusan.nama_jurusan')
            ->get();
        $mapels = DB::table('mata_pelajaran')->get(); 
        $gurus = DB::table('guru')->get();
        $ruangans = DB::table('ruangan')->get();
        $config = SystemConfiguration::first();

        // ---- Filter & pencarian (GET): q, putaran, hari, kelas ----
        $cycleAktif = $config->active_cycle ?? 'Putaran 1';
        $filter = [
            'q'       => trim((string) request('q', '')),
            // Default: putaran aktif. Pilih "Semua" => string kosong.
            'putaran' => request()->has('putaran') ? (string) request('putaran') : $cycleAktif,
            'hari'    => (string) request('hari', ''),
            'kelas'   => (string) request('kelas', ''),
        ];

        $query = JadwalPelajaran::with(['tahunAjaran', 'kelas.jurusan', 'mataPelajaran', 'guru', 'ruangan']);
        if ($guruLogin) {
            $query->where('id_guru', $guruLogin->id_guru);
        }
        if (in_array($filter['putaran'], ['Putaran 1', 'Putaran 2'], true)) {
            $query->where('cycle_type', $filter['putaran']);
        }
        if ($filter['hari'] !== '') {
            $query->where('hari', $filter['hari']);
        }
        if ($filter['kelas'] !== '') {
            $query->where('id_kelas', $filter['kelas']);
        }
        $jadwals = $query->get();

        // Pencarian bebas: kelas, jurusan, mapel, guru, ruangan, hari (tiap kata harus cocok)
        if ($filter['q'] !== '') {
            $kata = preg_split('/\s+/', mb_strtolower($filter['q']));
            $jadwals = $jadwals->filter(function ($j) use ($kata) {
                $hay = mb_strtolower(implode(' ', array_filter([
                    $j->hari,
                    $j->kelas?->tingkat,
                    $j->kelas?->id_jurusan,
                    $j->kelas?->jurusan?->nama_jurusan,
                    $j->id_mapel,
                    $j->mataPelajaran?->nama_mapel,
                    $j->guru?->nama_guru,
                    $j->ruangan?->nama_ruangan,
                ])));
                $hayRapat = str_replace(' ', '', $hay);
                foreach ($kata as $w) {
                    if ($w !== '' && !str_contains($hay, $w) && !str_contains($hayRapat, $w)) {
                        return false;
                    }
                }
                return true;
            })->values();
        }

        $totalJadwal = $jadwals->count();

        // Kelompokkan per kelas (urut id_kelas), isi tiap kelas urut hari lalu jam
        $urutHari = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6];
        $groups = $jadwals
            ->sortBy([
                fn ($a, $b) => ($urutHari[$a->hari] ?? 9) <=> ($urutHari[$b->hari] ?? 9),
                fn ($a, $b) => strcmp((string) $a->jam_mulai, (string) $b->jam_mulai),
            ])
            ->groupBy('id_kelas')
            ->sortKeys(SORT_NUMERIC);

        // 5 kelas per halaman
        $perPage = 5;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $jadwalGroups = $groups->slice(($page - 1) * $perPage, $perPage);
        $paginator = new LengthAwarePaginator($jadwalGroups, $groups->count(), $perPage, $page, [
            'path'  => request()->url(),
            'query' => request()->query(),
        ]);

        if ($guruLogin) {
            $jurnalList = Journal::with(['jadwal.kelas.jurusan', 'jadwal.mataPelajaran'])
                ->whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
                ->get();
        } else {
            $jurnalList = Journal::with(['jadwal.kelas.jurusan', 'jadwal.mataPelajaran'])->get();
        }

        $daftarSesi = JadwalPelajaran::getDaftarSesi();

        return view('jadwal.index', compact(
            'jadwals', 'jadwalGroups', 'paginator', 'filter', 'totalJadwal', 'cycleAktif',
            'jurnalList', 'config', 'tahunAjaran', 'kelas', 'mapels', 'gurus', 'ruangans', 'guruLogin', 'daftarSesi'
        ));
    }

    public function store(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis, silakan login kembali!']);
        }

        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->back()->withErrors(['error' => 'GAGAL! Jadwal telah dikunci oleh Waka Kurikulum.'])->withInput();
        }

        $username = session('username');
        $idRole = session('id_role');
        $guruLogin = Guru::where('nip', $username)->first();

        // Dukungan dual-role Wali Kelas (role 3) dan Guru (role 2)
        if (!$guruLogin && in_array($idRole, [2, 3])) {
            $nama = session('nama_lengkap') ?? $username;
            $guruLogin = Guru::where('nama_guru', $nama)->first();
            if (!$guruLogin) {
                $guruLogin = Guru::create([
                    'nip' => $username,
                    'nama_guru' => $nama,
                ]);
            }
        }

        if ($guruLogin) {
            $request->merge([
                'id_guru' => $guruLogin->id_guru,
            ]);
        }

        // Konversi pilihan Sesi Jam Pelajaran menjadi jam_mulai dan jam_selesai (HH:mm:ss)
        $sesiList = JadwalPelajaran::getDaftarSesi();

        if ($request->filled('sesi_mulai') && isset($sesiList[$request->sesi_mulai])) {
            $request->merge([
                'jam_mulai' => $sesiList[$request->sesi_mulai]['mulai'] . ':00',
            ]);
        }
        if ($request->filled('sesi_selesai') && isset($sesiList[$request->sesi_selesai])) {
            $request->merge([
                'jam_selesai' => $sesiList[$request->sesi_selesai]['selesai'] . ':00',
            ]);
        }

        if ($request->filled('sesi_mulai') && $request->filled('sesi_selesai')) {
            if ((int) $request->sesi_selesai < (int) $request->sesi_mulai) {
                return redirect()->back()->withErrors(['error' => 'GAGAL! Sesi selesai tidak boleh lebih awal daripada sesi mulai.'])->withInput();
            }
        }

        $request->validate([
            'id_tahun_ajaran' => 'required',
            'id_kelas' => 'required',
            'id_mapel' => 'required',
            'id_guru' => 'required',
            'id_ruangan_default' => 'required',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);

        $activeCycle = $config->active_cycle ?? 'Putaran 1';

        $isConflict = JadwalPelajaran::where('hari', $request->hari)
            ->where('cycle_type', $activeCycle)
            ->where(function ($query) use ($request) {
                $query->where('id_ruangan_default', $request->id_ruangan_default)
                      ->orWhere('id_kelas', $request->id_kelas)
                      ->orWhere('id_guru', $request->id_guru);
            })
            ->where('jam_mulai', '<', $request->jam_selesai)
            ->where('jam_selesai', '>', $request->jam_mulai)
            ->exists();

        if ($isConflict) {
            return redirect()->back()->withErrors(['error' => 'GAGAL! Jadwal bentrok dengan waktu, ruangan, atau kelas lain pada putaran ini.'])->withInput();
        }

        JadwalPelajaran::create([
            'id_tahun_ajaran'    => $request->id_tahun_ajaran,
            'id_kelas'           => $request->id_kelas,
            'id_mapel'           => $request->id_mapel,
            'id_guru'            => $request->id_guru,
            'id_ruangan_default' => $request->id_ruangan_default,
            'hari'               => $request->hari,
            'jam_mulai'          => $request->jam_mulai,
            'jam_selesai'        => $request->jam_selesai,
            'cycle_type'         => $activeCycle,
        ]);

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil ditambahkan!');
    }

    public function updateConfig(Request $request)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Hanya Waka Kurikulum yang dapat mengubah konfigurasi jadwal.']);
        }

        $config = SystemConfiguration::first();
        if ($config) {
            $config->update([
                'active_cycle' => $request->active_cycle,
                'is_schedule_locked' => $request->is_schedule_locked,
            ]);
        }
        
        return redirect()->back()->with('success', 'Konfigurasi berhasil diperbarui!');
    }

    // ==========================================
    // FITUR MANAJEMEN AKUN & ROLE (KHUSUS WAKA)
    // ==========================================

    public function indexAkun()
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Hanya Waka Kurikulum yang dapat mengelola akun.']);
        }

        $roles = DB::table('role')->get();

        $users = DB::table('users')
            ->leftJoin('role', 'users.id_role', '=', 'role.id_role')
            ->leftJoin('siswa', 'users.username', '=', 'siswa.nisn')
            ->leftJoin('kelas as k_siswa', 'siswa.id_kelas', '=', 'k_siswa.id_kelas')
            ->leftJoin('jurusan as j_siswa', 'k_siswa.id_jurusan', '=', 'j_siswa.id_jurusan')
            ->select(
                'users.*',
                'role.nama_role',
                'siswa.id_kelas as siswa_id_kelas',
                DB::raw("TRIM(CONCAT(COALESCE(k_siswa.tingkat,''), ' ', COALESCE(k_siswa.id_jurusan,''))) as siswa_nama_kelas")
            )
            ->orderBy('users.id_user', 'desc')
            ->get();

        $daftarKelas = DB::table('kelas')
            ->leftJoin('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
            ->select('kelas.*', 'jurusan.nama_jurusan')
            ->orderBy('kelas.tingkat')
            ->orderBy('jurusan.nama_jurusan')
            ->get();

        return view('jadwal.manajemen_akun', compact('users', 'roles', 'daftarKelas'));
    }

    public function storeAkun(Request $request)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $request->validate([
            'username'     => 'required|string|max:50|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'password'     => 'required|min:5',
            'id_role'      => 'required',
            'email'        => 'nullable|email|max:100|unique:users,email',
        ], [
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah dipakai oleh akun lain.',
            'username.required'     => 'Username / NIP / NISN wajib diisi.',
            'username.unique'       => 'Username sudah dipakai oleh pengguna lain.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'id_role.required'      => 'Role / hak akses wajib dipilih.',
            'password.required'     => 'Kata sandi wajib diisi.',
            'password.min'          => 'Kata sandi minimal 5 karakter.',
        ]);

        // 1. Masukkan data ke tabel users terlebih dahulu
        DB::table('users')->insert([
            'username'      => $request->username,
            'password_hash' => bcrypt($request->password),
            'nama_lengkap'  => $request->nama_lengkap,
            'id_role'       => $request->id_role,
            'email'         => $request->filled('email') ? strtolower(trim($request->email)) : null,
            'is_aktif'      => 1,
        ]);

        // 2. JIKA role yang dipilih adalah GURU (id_role == 2) atau WALI KELAS (id_role == 3), 
        // masukkan otomatis datanya ke dalam tabel 'guru' jika belum ada
        if ($request->id_role == 2 || $request->id_role == 3) {
            $cekGuru = DB::table('guru')->where('nip', $request->username)->exists();
            if (!$cekGuru) {
                DB::table('guru')->insert([
                    'nip'       => $request->username,
                    'nama_guru' => $request->nama_lengkap,
                ]);
            }
        }

        // 3. JIKA role yang dipilih adalah SISWA (id_role == 5),
        // masukkan otomatis datanya ke dalam tabel 'siswa' jika belum ada
        if ($request->id_role == 5) {
            $cekSiswa = DB::table('siswa')->where('nisn', $request->username)->exists();
            if (!$cekSiswa) {
                DB::table('siswa')->insert([
                    'nisn'        => $request->username,
                    'nama_siswa'  => $request->nama_lengkap,
                    'status_utama'=> 'Aktif',
                ]);
            }
        }

        return redirect()->route('akun.index')->with('success', 'Akun baru "' . $request->nama_lengkap . '" berhasil dibuat!');
    }

    public function updateAkun(Request $request, $id)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Hanya Waka Kurikulum yang dapat mengubah data akun.']);
        }

        $user = DB::table('users')->where('id_user', $id)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'Data pengguna tidak ditemukan!']);
        }

        $request->validate([
            'username'     => 'required|string|max:50|unique:users,username,' . $id . ',id_user',
            'nama_lengkap' => 'required|string|max:100',
            'id_role'      => 'required',
            'email'        => 'nullable|email|max:100|unique:users,email,' . $id . ',id_user',
            'password'     => 'nullable|min:5',
            'is_aktif'     => 'required|in:0,1',
        ], [
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah digunakan oleh akun lain.',
            'username.required'     => 'Username / NIP / NISN wajib diisi.',
            'username.unique'       => 'Username sudah digunakan oleh akun lain.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'id_role.required'      => 'Role wajib dipilih.',
            'password.min'          => 'Password baru minimal 5 karakter jika diisi.',
        ]);

        $oldUsername = $user->username;

        $updateData = [
            'username'     => $request->username,
            'nama_lengkap' => $request->nama_lengkap,
            'id_role'      => $request->id_role,
            'email'        => $request->filled('email') ? strtolower(trim($request->email)) : null,
            'is_aktif'     => $request->is_aktif,
        ];

        if ($request->filled('password')) {
            $updateData['password_hash'] = bcrypt($request->password);
        }

        DB::table('users')->where('id_user', $id)->update($updateData);

        // Sinkronisasi data ke tabel guru jika role = 2 (Guru) atau role = 3 (Wali Kelas)
        if ($request->id_role == 2 || $request->id_role == 3) {
            $guru = DB::table('guru')->where('nip', $oldUsername)->first();
            if ($guru) {
                DB::table('guru')->where('nip', $oldUsername)->update([
                    'nip'       => $request->username,
                    'nama_guru' => $request->nama_lengkap,
                ]);
            } else {
                DB::table('guru')->insert([
                    'nip'       => $request->username,
                    'nama_guru' => $request->nama_lengkap,
                ]);
            }
        }

        // Sinkronisasi data ke tabel siswa jika role = 5 (Siswa)
        if ($request->id_role == 5) {
            $siswa = DB::table('siswa')->where('nisn', $oldUsername)->first();
            if ($siswa) {
                DB::table('siswa')->where('nisn', $oldUsername)->update([
                    'nisn'       => $request->username,
                    'nama_siswa' => $request->nama_lengkap,
                ]);
            }
        }

        return redirect()->route('akun.index')->with('success', 'Data akun "' . $request->nama_lengkap . '" berhasil diperbarui!');
    }

    public function deleteAkun($id)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        if (session('id_user') == $id) {
            return redirect()->back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login!']);
        }

        $user = DB::table('users')->where('id_user', $id)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'Pengguna tidak ditemukan!']);
        }

        try {
            DB::beginTransaction();

            // Lepaskan referensi verified_by di permohonan izin (jika ada)
            DB::table('leave_requests')->where('verified_by', $id)->update(['verified_by' => null]);

            // Hapus sesi aktif pengguna jika ada di tabel sessions
            DB::table('sessions')->where('user_id', $id)->delete();

            // Hapus user
            DB::table('users')->where('id_user', $id)->delete();

            DB::commit();

            return redirect()->route('akun.index')->with('success', 'Akun "' . $user->nama_lengkap . '" (' . $user->username . ') berhasil dihapus!');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus akun: ' . $e->getMessage()]);
        }
    }

    /**
     * Menghapus banyak akun pengguna sekaligus (Bulk Delete)
     */
    public function bulkDeleteAkun(Request $request)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer',
        ], [
            'ids.required' => 'Pilih minimal satu akun yang ingin dihapus.',
        ]);

        $currentUserId = session('id_user');
        // Jangan biarkan akun user yang sedang aktif login ikut terhapus
        $idsToDelete = array_values(array_filter($request->ids, function ($id) use ($currentUserId) {
            return (int) $id !== (int) $currentUserId;
        }));

        if (empty($idsToDelete)) {
            return redirect()->back()->withErrors(['error' => 'Tidak ada akun valid yang dapat dihapus. Anda tidak dapat menghapus akun Anda sendiri yang sedang login.']);
        }

        try {
            DB::beginTransaction();

            $count = count($idsToDelete);

            // Lepaskan referensi verified_by di permohonan izin jika ada
            DB::table('leave_requests')->whereIn('verified_by', $idsToDelete)->update(['verified_by' => null]);

            // Hapus sesi pengguna jika ada di tabel sessions
            DB::table('sessions')->whereIn('user_id', $idsToDelete)->delete();

            // Hapus dari tabel users
            DB::table('users')->whereIn('id_user', $idsToDelete)->delete();

            DB::commit();

            return redirect()->route('akun.index')->with('success', "Berhasil menghapus {$count} akun pengguna sekaligus secara permanen!");

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus akun terpilih: ' . $e->getMessage()]);
        }
    }

    /**
     * Download template Excel / CSV umum untuk import pengguna
     */
    public function downloadTemplate()
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $csv = ExcelImportService::generateTemplateCsv();
        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_pengguna.csv"',
        ]);
    }

    /**
     * Download template format Daftar Hadir Siswa per Kelas (seperti format buku presensi)
     */
    public function downloadTemplateSiswa()
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $csv = ExcelImportService::generateTemplateDaftarHadir();
        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_daftar_hadir_siswa.csv"',
        ]);
    }

    /**
     * Mengimpor data pengguna baru / data daftar hadir siswa dari file Excel (.xlsx) atau CSV (.csv)
     * Mendukung:
     * 1. Format Daftar Hadir Siswa Sekolah (seperti presensi kelas: ada KELAS : X RPL 1, NIS, NAMA SISWA, multi-sheet per kelas)
     * 2. Format Tabel Pengguna Umum (username, nama_lengkap, role, password, no_hp)
     */
    public function importAkun(Request $request)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        // Tingkatkan batas waktu & memori untuk file Excel yang memuat banyak siswa/kelas
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $request->validate([
            'file_excel'      => 'required|file|max:10240',
            'target_id_kelas' => 'nullable|string',
            'default_role'    => 'nullable|in:2,3,4,5,7',
        ], [
            'file_excel.required' => 'File Excel / CSV wajib diunggah.',
            'file_excel.file'     => 'File yang diunggah tidak valid.',
            'file_excel.max'      => 'Ukuran file maksimal adalah 10MB.',
        ]);

        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'csv'])) {
            return redirect()->back()->withErrors(['file_excel' => 'Format file tidak didukung. Harap unggah file berekstensi .xlsx atau .csv!'])->withInput();
        }

        try {
            $sheets = ExcelImportService::parseAllSheets($file->getPathname(), $ext);

            if (empty($sheets)) {
                return redirect()->back()->withErrors(['error' => 'File Excel tidak memiliki data untuk diimpor.'])->withInput();
            }

            // Ambil daftar kelas master dari database untuk pemetaan otomatis
            $daftarKelas = DB::table('kelas')
                ->leftJoin('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan')
                ->select('kelas.*', 'jurusan.nama_jurusan')
                ->get();

            // Cache data pengguna dan siswa yang sudah ada ke memori (O(1) lookup super cepat)
            $existingUsers = DB::table('users')->pluck('id_role', 'username')->toArray();
            $existingSiswa = DB::table('siswa')->pluck('id_kelas', 'nisn')->toArray();

            // Hash default dihitung 1x di awal agar tidak membebani CPU / timeout di dalam perulangan ratusan siswa
            $defaultPasswordHash = bcrypt('123456');
            $passwordCache = ['123456' => $defaultPasswordHash];

            $totalSiswaCreated = 0;
            $totalSiswaUpdated = 0;
            $totalGeneralUsers = 0;
            $classesProcessed = [];
            $skippedUsernames = [];

            DB::beginTransaction();

            foreach ($sheets as $sheet) {
                $rows = $sheet['rows'];
                if (count($rows) < 2) {
                    continue;
                }

                $analysis = ExcelImportService::analyzeSheet($rows, $sheet['sheet_name']);

                if ($analysis['format'] === 'DAFTAR_HADIR_SISWA') {
                    // Tentukan kelas target awal:
                    // 1. Hasil deteksi otomatis dari tulisan "KELAS : ..." di header atau nama sheet
                    // 2. Pilihan manual admin di form (jika ada) sebagai fallback
                    $detectedKelasId = self::matchKelasId($analysis['detected_kelas'], $daftarKelas);
                    $currentTargetKelasId = $detectedKelasId ?: ($request->target_id_kelas ?: null);
                    $currentKelasLabel = $analysis['detected_kelas'] ?? ($currentTargetKelasId ? "ID Kelas {$currentTargetKelasId}" : 'Tanpa Kelas');

                    if (!empty($currentKelasLabel) && !in_array($currentKelasLabel, $classesProcessed)) {
                        $classesProcessed[] = $currentKelasLabel;
                    }

                    $headerIdx = $analysis['header_row_index'];
                    $colNis = $analysis['col_nis'];
                    $colNama = $analysis['col_nama'];
                    $colKelas = $analysis['col_kelas'];

                    for ($i = $headerIdx + 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        $rowText = implode(' ', array_filter($row));

                        // 1. Cek apakah di tengah baris terdapat pergantian kelas (multi-kelas vertikal dalam 1 sheet)
                        if (preg_match('/kelas\s*[:=\-]?\s*([a-zA-Z0-9\s\-]+)/i', $rowText, $mKelas)) {
                            $newKelasText = trim($mKelas[1]);
                            $newKelasText = preg_replace('/(tahun|semester|guru|jumlah).*/i', '', $newKelasText);
                            $newKelasText = trim($newKelasText);
                            if (!empty($newKelasText)) {
                                $currentKelasLabel = $newKelasText;
                                $matchedId = self::matchKelasId($newKelasText, $daftarKelas);
                                if ($matchedId) {
                                    $currentTargetKelasId = $matchedId;
                                }
                                if (!in_array($currentKelasLabel, $classesProcessed)) {
                                    $classesProcessed[] = $currentKelasLabel;
                                }
                                continue;
                            }
                        }

                        // 2. Abaikan baris header tabel berulang, rekapitulasi, atau tanda tangan footer
                        $rowUpper = strtoupper($rowText);
                        if (preg_match('/(DAFTAR HADIR|NOMOR|NAMA SISWA|PERTEMUAN|JUMLAH|TOTAL|REKAP|GURU|WALI\s*KELAS|MENGETAHUI|KEPALA\s*SEKOLAH)/', $rowUpper)) {
                            continue;
                        }

                        $rawNis = isset($row[$colNis]) ? trim((string) $row[$colNis]) : '';
                        $rawNama = isset($row[$colNama]) ? trim((string) $row[$colNama]) : '';

                        // Pengaman ekstra: Jika NIS terisi 1-2 digit (nomor urut) dan kolom sebelahnya ada angka panjang (NIS asli)
                        if (strlen($rawNis) <= 2 && isset($row[$colNis + 1]) && preg_match('/^[0-9]{4,12}$/', trim((string)$row[$colNis + 1]))) {
                            $rawNis = trim((string)$row[$colNis + 1]);
                            if (isset($row[$colNis + 2]) && !is_numeric(trim((string)$row[$colNis + 2]))) {
                                $rawNama = trim((string)$row[$colNis + 2]);
                            }
                        }

                        // Pengaman jika posisi NIS dan Nama tertukar (misal NIS berisi huruf dan Nama berisi angka murni)
                        if (is_numeric($rawNama) && !is_numeric($rawNis)) {
                            $tmp = $rawNis;
                            $rawNis = $rawNama;
                            $rawNama = $tmp;
                        }

                        // Bersihkan NIS (ambil karakter alfanumerik)
                        $nis = preg_replace('/[^0-9a-zA-Z]/', '', $rawNis);

                        // NIS harus minimal 3 karakter & nama tidak boleh kosong atau angka murni
                        if (strlen($nis) < 3 || empty($rawNama) || is_numeric($rawNama)) {
                            continue;
                        }

                        // Jika ada kolom kelas khusus di baris data, prioritaskan kelas tersebut
                        $rowKelasId = $currentTargetKelasId;
                        if ($colKelas !== null && isset($row[$colKelas]) && !empty($row[$colKelas])) {
                            $detectedRowKelas = self::matchKelasId(trim((string) $row[$colKelas]), $daftarKelas);
                            if ($detectedRowKelas) {
                                $rowKelasId = $detectedRowKelas;
                            }
                        }

                        // 1. Sinkronkan ke tabel users (role 5 = Siswa)
                        if (!isset($existingUsers[$nis])) {
                            DB::table('users')->insert([
                                'username'      => $nis,
                                'nama_lengkap'  => $rawNama,
                                'password_hash' => $defaultPasswordHash,
                                'id_role'       => 5,                 // Siswa
                                'is_aktif'      => 1,
                            ]);
                            $existingUsers[$nis] = 5;
                            $totalSiswaCreated++;
                        } else {
                            // Update nama & pastikan role siswa
                            DB::table('users')->where('username', $nis)->update([
                                'nama_lengkap' => $rawNama,
                                'id_role'      => 5,
                            ]);
                            $totalSiswaUpdated++;
                        }

                        // 2. Sinkronkan ke tabel siswa (master siswa & penempatan kelas)
                        if (!isset($existingSiswa[$nis])) {
                            DB::table('siswa')->insert([
                                'nisn'         => $nis,
                                'nama_siswa'   => $rawNama,
                                'id_kelas'     => $rowKelasId,
                                'status_utama' => 'Aktif',
                            ]);
                            $existingSiswa[$nis] = $rowKelasId;
                        } else {
                            $updateSiswa = ['nama_siswa' => $rawNama, 'status_utama' => 'Aktif'];
                            if ($rowKelasId) {
                                $updateSiswa['id_kelas'] = $rowKelasId;
                            }
                            DB::table('siswa')->where('nisn', $nis)->update($updateSiswa);
                        }
                    }

                } else {
                    // FORMAT TABEL PENGGUNA UMUM / DAFTAR WALI KELAS / DAFTAR GURU BK
                    $sheetNameLower = strtolower($sheet['sheet_name']);
                    $currentSectionRole = null;
                    if (str_contains($sheetNameLower, 'wali')) {
                        $currentSectionRole = 3; // Wali Kelas
                    } elseif (str_contains($sheetNameLower, 'bk') || str_contains($sheetNameLower, 'konseling')) {
                        $currentSectionRole = 7; // Guru BK
                    }

                    for ($i = 0; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        $rowFiltered = array_filter(array_map('trim', $row));
                        if (empty($rowFiltered)) continue;

                        $rowText = strtolower(implode(' ', $rowFiltered));

                        // 1. Deteksi Baris Judul Bagian / Banner
                        $isSectionBanner = false;
                        if (str_contains($rowText, 'wali') && str_contains($rowText, 'bk')) {
                            // Judul Dokumen Utama (memuat wali dan bk sekaligus) -> abaikan sebagai banner
                            $isSectionBanner = true;
                        } elseif (preg_match('/(guru\s*bk|bimbingan\s*konseling|\b(bk|bp\/bk)\b)/i', $rowText) && !str_contains($rowText, 'wali') && (str_contains($rowText, 'daftar') || !preg_match('/^[0-9]/', reset($rowFiltered)))) {
                            $currentSectionRole = 7; // Masuk ke bagian Guru BK
                            $isSectionBanner = true;
                        } elseif (preg_match('/(wali\s*kelas|daftar\s*wali)/i', $rowText) && !str_contains($rowText, 'bk') && (str_contains($rowText, 'daftar') || !preg_match('/^[0-9]/', reset($rowFiltered)))) {
                            $currentSectionRole = 3; // Masuk ke bagian Wali Kelas
                            $isSectionBanner = true;
                        }

                        // 2. Deteksi Baris Header Tabel Kolom (misal "NO | KELAS | NAMA WALI KELAS | NIP")
                        $isHeaderRow = (
                            preg_match('/\b(no|nomor|urt)\b/i', $rowText) &&
                            preg_match('/\b(nama|nip|kelas|keterangan|tugas|jabatan)\b/i', $rowText)
                        );

                        if ($isSectionBanner || $isHeaderRow) {
                            continue; // Lewati judul & header kolom
                        }

                        $candNip = null;
                        $candNama = null;
                        $candKelas = null;
                        $cellRole = null;

                        foreach ($row as $val) {
                            $val = trim((string)$val);
                            if (empty($val)) continue;
                            $valLower = strtolower($val);

                            // NIP: 8-18 digit angka murni
                            if (preg_match('/^[0-9]{8,18}$/', $val) && $candNip === null) {
                                $candNip = $val;
                            }
                            // Format Kelas (misal "XII RPL 2", "X AK 1", "XI TKJ 1")
                            elseif (preg_match('/^(X|XI|XII|10|11|12)\s*[\-_]?\s*[A-Z0-9\s]+$/i', $val) && strlen($val) <= 15 && $candKelas === null) {
                                $candKelas = $val;
                            }
                            // Nama Guru / Pengajar (huruf, gelar, minimal 4 karakter)
                            elseif (
                                preg_match('/^[a-zA-Z\s\.\',\-]{4,}$/', $val) &&
                                !preg_match('/^(senin|selasa|rabu|kamis|jumat|sabtu|tahun|semester|guru bk|wali kelas|bimbingan konseling|daftar|keterangan|koordinator|wali|guru)$/i', $val) &&
                                !preg_match('/^(daftar\s*(wali|guru|bk|hadir|siswa|bimbingan))/i', $val) &&
                                $candNama === null
                            ) {
                                $candNama = $val;
                            }

                            if (str_contains($valLower, 'wali')) {
                                $cellRole = 3;
                            } elseif (str_contains($valLower, 'bk') || str_contains($valLower, 'konseling') || str_contains($valLower, 'bimbingan')) {
                                $cellRole = 7;
                            }
                        }

                        if (empty($candNama) || is_numeric($candNama)) {
                            continue;
                        }

                        // Tentukan Role Baris Ini Secara Presisi
                        if ($request->filled('default_role')) {
                            $roleId = (int) $request->default_role;
                        } else {
                            $hasBkKeyword = (str_contains($rowText, 'guru bk') || str_contains($rowText, 'bimbingan') || str_contains($rowText, 'konseling') || preg_match('/\b(bk|bp\/bk)\b/i', $rowText));
                            $hasWaliKeyword = (str_contains($rowText, 'wali') || str_contains($rowText, 'walikelas'));

                            // JIKA MEMILIKI KELAS SPESIFIK (misal "XII RPL 2", "X AK 1") -> MUTLAK WALI KELAS!
                            if (!empty($candKelas) || $hasWaliKeyword || $cellRole === 3) {
                                $roleId = 3; // Wali Kelas
                            } elseif ($hasBkKeyword || $cellRole === 7) {
                                $roleId = 7; // Guru BK
                            } elseif ($currentSectionRole !== null) {
                                $roleId = $currentSectionRole;
                            } else {
                                $roleId = 2; // Guru Pengampu
                            }
                        }

                        // Tentukan Username
                        $username = $candNip;
                        if (empty($username)) {
                            if ($roleId === 3 && $candKelas) {
                                $cleanK = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $candKelas));
                                $username = 'wali_' . $cleanK;
                            } elseif ($roleId === 7) {
                                $cleanedName = preg_replace('/^(dra|drs|dr|mr|mrs|h|hj|ir|prof)\.?\s+/i', '', trim($candNama));
                                $firstWord = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', trim($cleanedName))[0] ?? 'guru'));
                                $username = 'bk_' . ($firstWord ?: 'guru');
                            } else {
                                $cleanedName = preg_replace('/^(dra|drs|dr|mr|mrs|h|hj|ir|prof)\.?\s+/i', '', trim($candNama));
                                $firstWord = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', trim($cleanedName))[0] ?? 'guru'));
                                $username = 'guru_' . ($firstWord ?: 'pengampu');
                            }
                        }

                        // Pastikan username unik di perulangan batch ini
                        $baseUser = $username;
                        $counter = 1;
                        while (isset($existingUsers[$username]) && $existingUsers[$username] == $roleId) {
                            break;
                        }

                        $passToHash = '123456';
                        $hashedPassword = $defaultPasswordHash;

                        if (!isset($existingUsers[$username])) {
                            DB::table('users')->insert([
                                'username'      => $username,
                                'nama_lengkap'  => $candNama,
                                'password_hash' => $hashedPassword,
                                'id_role'       => $roleId,
                                'is_aktif'      => 1,
                            ]);
                            $existingUsers[$username] = $roleId;
                            $totalGeneralUsers++;
                        } else {
                            // JIKA ROLE AKUN DI DATABASE BERBEDA DENGAN HASIL ANALISIS (misal sebelumnya keliru terinput sebagai BK / Guru Pengampu):
                            // Otomatis koreksi dan perbarui role-nya ke yang benar!
                            if ($existingUsers[$username] != $roleId) {
                                DB::table('users')->where('username', $username)->update([
                                    'id_role'      => $roleId,
                                    'nama_lengkap' => $candNama,
                                ]);
                                $existingUsers[$username] = $roleId;
                                $totalGeneralUsers++;
                            } else {
                                $skippedUsernames[] = "{$username} ({$candNama})";
                            }
                        }

                        // Sinkronkan ke tabel guru jika role Guru, Wali Kelas, atau Guru BK
                        if (in_array($roleId, [2, 3, 7])) {
                            $cekGuru = DB::table('guru')->where('nip', $username)->exists();
                            if (!$cekGuru) {
                                DB::table('guru')->insert([
                                    'nip'       => $username,
                                    'nama_guru' => $candNama,
                                ]);
                            }
                        }

                        // Hanya sinkronkan ke siswa jika role benar-benar Siswa (5)
                        if ($roleId == 5 && strlen($username) >= 3 && !preg_match('/^(X|XI|XII|10|11|12)\b/i', $candNama)) {
                            if (!isset($existingSiswa[$username])) {
                                DB::table('siswa')->updateOrInsert(
                                    ['nisn' => $username],
                                    [
                                        'nama_siswa'   => $candNama,
                                        'status_utama' => 'Aktif',
                                    ]
                                );
                                $existingSiswa[$username] = null;
                            }
                        }
                    }
                }
            }

            DB::commit();

            // Susun pesan hasil import
            $msgParts = [];
            $totalSiswa = $totalSiswaCreated + $totalSiswaUpdated;

            if ($totalSiswa > 0) {
                $classesStr = count($classesProcessed) > 0 ? ' (' . implode(', ', $classesProcessed) . ')' : '';
                $msgParts[] = "Berhasil memproses {$totalSiswa} data siswa{$classesStr}: {$totalSiswaCreated} akun baru dibuat, {$totalSiswaUpdated} data siswa diperbarui.";
            }

            if ($totalGeneralUsers > 0) {
                $msgParts[] = "Berhasil menambahkan {$totalGeneralUsers} akun pengguna umum.";
            }

            if (empty($msgParts)) {
                $msgParts[] = "Tidak ada data baru yang diproses dari file Excel.";
            }

            if (count($skippedUsernames) > 0) {
                $msgParts[] = count($skippedUsernames) . " akun dilewati karena username/NIP sudah terdaftar.";
            }

            return redirect()->route('akun.index')->with('success', implode(' ', $msgParts));

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal membaca atau mengimpor file Excel: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Memetakan teks nama kelas (misal "X RPL 1", "10 RPL 1", "X-RPL-1") ke id_kelas di database
     */
    private static function matchKelasId(?string $search, $daftarKelas): ?string
    {
        if (empty($search)) {
            return null;
        }

        $rawSearch = trim($search);

        // 1. Coba pencocokan persis pada id_kelas langsung
        foreach ($daftarKelas as $k) {
            if (strcasecmp((string)$k->id_kelas, $rawSearch) === 0) {
                return $k->id_kelas;
            }
        }

        $cleanSearch = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $rawSearch)));
        // Normalisasi angka desimal ke Romawi: 10 -> X, 11 -> XI, 12 -> XII
        $normSearch = preg_replace('/^10/', 'X', $cleanSearch);
        $normSearch = preg_replace('/^11/', 'XI', $normSearch);
        $normSearch = preg_replace('/^12/', 'XII', $normSearch);

        // 2. Coba pencocokan alfanumerik bersih pada id_kelas (misal "X-RPL-1" cocok dengan "XRPL1" atau "10 RPL 1")
        foreach ($daftarKelas as $k) {
            $kClean = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$k->id_kelas)));
            $kNorm = preg_replace('/^10/', 'X', $kClean);
            $kNorm = preg_replace('/^11/', 'XI', $kNorm);
            $kNorm = preg_replace('/^12/', 'XII', $kNorm);

            if ($kClean === $cleanSearch || $kNorm === $normSearch) {
                return $k->id_kelas;
            }
        }

        // 3. Coba pencocokan pada tingkat + id_jurusan (misal "X" + "RPL1" -> "XRPL1")
        foreach ($daftarKelas as $k) {
            $shortName = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', ($k->tingkat ?? '') . ($k->id_jurusan ?? ''))));
            $shortNameNorm = preg_replace('/^10/', 'X', $shortName);
            if ($shortName === $cleanSearch || $shortNameNorm === $normSearch) {
                return $k->id_kelas;
            }
        }

        // 4. Coba pencocokan pada tingkat + nama_jurusan (misal "X" + "Rekayasa Perangkat Lunak 1")
        foreach ($daftarKelas as $k) {
            $fullName = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', ($k->tingkat ?? '') . ($k->nama_jurusan ?? ''))));
            $fullNameNorm = preg_replace('/^10/', 'X', $fullName);
            if ($fullName === $cleanSearch || $fullNameNorm === $normSearch) {
                return $k->id_kelas;
            }
        }

        // 5. Pencocokan substring (jika nama jurusan ada di dalam teks pencarian)
        foreach ($daftarKelas as $k) {
            $jurusanClean = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $k->nama_jurusan ?? '')));
            if (!empty($jurusanClean) && (str_contains($normSearch, $jurusanClean) || str_contains($jurusanClean, $normSearch))) {
                $tingkatClean = strtoupper(trim($k->tingkat ?? ''));
                if (empty($tingkatClean) || str_starts_with($normSearch, $tingkatClean)) {
                    return $k->id_kelas;
                }
            }
        }

        return null;
    }

    /**
     * Memetakan nama atau angka role menjadi id_role yang valid
     */
    private static function resolveRoleId(string $rawRole, ?int $defaultFallback = null): ?int
    {
        $clean = strtolower(trim($rawRole));

        if (is_numeric($clean)) {
            $num = (int) $clean;
            if (in_array($num, [1, 2, 3, 4, 5, 7])) {
                return $num;
            }
        }

        if (str_contains($clean, 'waka') || str_contains($clean, 'kurikulum') || str_contains($clean, 'admin')) {
            return 4;
        } elseif (str_contains($clean, 'wali')) {
            return 3;
        } elseif (str_contains($clean, 'bk') || str_contains($clean, 'konseling')) {
            return 7;
        } elseif (str_contains($clean, 'guru') || str_contains($clean, 'pengampu') || str_contains($clean, 'pengajar')) {
            return 2;
        } elseif (str_contains($clean, 'siswa') || str_contains($clean, 'murid') || str_contains($clean, 'pelajar')) {
            return 5;
        }

        return $defaultFallback;
    }
}
