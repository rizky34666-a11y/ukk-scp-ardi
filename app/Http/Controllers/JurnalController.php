<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JurnalController extends Controller
{
    public function index()
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $user = Auth::user();
        $username = $user ? $user->username : session('username');
        $idRole = session('id_role') ?? ($user ? $user->id_role : null);

        $guru = Guru::where('nip', $username)->first();

        // Dukungan dual-role: Jika Wali Kelas (role 3) atau Guru (role 2) belum ada di tabel guru, sinkronkan otomatis
        if (!$guru) {
            $nama = session('nama_lengkap') ?? ($user ? $user->nama_lengkap : $username);
            $guru = Guru::where('nama_guru', $nama)->first();
            if (!$guru && in_array($idRole, [2, 3])) {
                $guru = Guru::create([
                    'nip' => $username,
                    'nama_guru' => $nama,
                ]);
            }
        }

        if (!$guru) {
            return redirect()->back()->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $now = \Carbon\Carbon::now();
        $mapHari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $hariIni = $mapHari[$now->dayOfWeekIso] ?? null;

        $jadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'tahunAjaran', 'ruangan'])
            ->where('id_guru', $guru->id_guru)
            ->get();

        // Hitung batas pengisian jurnal (maksimal 5 jam setelah jam_selesai)
        $jadwals->transform(function ($j) use ($now, $hariIni) {
            $isHariIni = ($j->hari === $hariIni);
            $j->is_hari_ini = $isHariIni;

            if ($isHariIni && !empty($j->jam_selesai)) {
                $selesaiTime = \Carbon\Carbon::parse($now->toDateString() . ' ' . $j->jam_selesai);
                $deadline5Jam = $selesaiTime->copy()->addHours(5);
                $j->deadline_time = $deadline5Jam;
                $j->deadline_formatted = $deadline5Jam->format('H:i') . ' WIB';
                $j->jam_selesai_formatted = $selesaiTime->format('H:i') . ' WIB';
                $j->is_expired = $now->greaterThan($deadline5Jam);
                if ($j->is_expired) {
                    $selisih = $now->diffForHumans($deadline5Jam, true);
                    $j->expired_reason = "Telah lewat batas 5 jam setelah jam pembelajaran selesai (KBM selesai {$selesaiTime->format('H:i')}, batas {$deadline5Jam->format('H:i')} WIB, terlewat {$selisih})";
                }
            } else {
                $j->is_expired = true;
                $j->deadline_formatted = '-';
                if (!$isHariIni) {
                    $j->expired_reason = "Jadwal ini untuk hari {$j->hari}. Jurnal hanya dapat diisi pada hari KBM bersangkutan maksimal 5 jam setelah pembelajaran selesai.";
                }
            }
            return $j;
        });
        
        $jurnalList = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran', 'attendances.siswa'])
            ->whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Tentukan jadwal aktif awal: prioritaskan jadwal hari ini yang belum kedaluwarsa
        $firstJadwal = $jadwals->where('is_expired', false)->first() ?? $jadwals->first();
        $selectedScheduleId = request('schedule_id', $firstJadwal?->id_jadwal);
        $activeJadwal = $jadwals->where('id_jadwal', $selectedScheduleId)->first() ?? $firstJadwal;
        $activeKelasId = $activeJadwal?->id_kelas;

        // Ambil data siswa dari database KHUSUS untuk kelas aktif
        $siswaList = collect();
        if ($activeKelasId) {
            $siswaList = Siswa::where('id_kelas', $activeKelasId)
                ->where('status_utama', 'Aktif')
                ->orderBy('nama_siswa', 'asc')
                ->get();
        }

        $kelasIds = $jadwals->pluck('id_kelas')
            ->merge($jurnalList->pluck('jadwal.id_kelas'))
            ->filter()
            ->unique();
        
        // Ambil data siswa yang terdaftar di kelas-kelas yang diampu oleh guru ini dari database
        $siswaByKelas = Siswa::whereIn('id_kelas', $kelasIds)
            ->where('status_utama', 'Aktif')
            ->orderBy('nama_siswa', 'asc')
            ->get(['id_siswa', 'nisn', 'nama_siswa', 'id_kelas'])
            ->groupBy('id_kelas');

        // Seluruh siswa aktif sebagai fallback jika ada kelas baru yang belum di-assign siswa
        $semuaSiswa = Siswa::where('status_utama', 'Aktif')
            ->orderBy('nama_siswa', 'asc')
            ->get(['id_siswa', 'nisn', 'nama_siswa', 'id_kelas']);

        // Ambil data izin siswa yang sudah Disetujui (Approved) oleh Wali Kelas / Guru BK untuk hari ini
        $today = date('Y-m-d');
        $approvedLeavesToday = LeaveRequest::where('status_approval', 'Approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get()
            ->keyBy('student_id');

        // Fungsi pembantu untuk menandai siswa yang sudah memiliki izin disetujui
        $attachApprovedLeave = function ($siswa) use ($approvedLeavesToday) {
            if ($approvedLeavesToday->has($siswa->id_siswa)) {
                $leave = $approvedLeavesToday->get($siswa->id_siswa);
                $isSakit = (stripos($leave->reason, 'sakit') !== false);
                $siswa->is_approved_leave = true;
                $siswa->leave_status = $isSakit ? 'Sakit' : 'Izin';
                $siswa->leave_reason = $leave->reason;
            } else {
                $siswa->is_approved_leave = false;
                $siswa->leave_status = 'Alfa';
                $siswa->leave_reason = '';
            }
            return $siswa;
        };

        $siswaList = $siswaList->map($attachApprovedLeave);

        $siswaByKelas = $siswaByKelas->map(function ($group) use ($attachApprovedLeave) {
            return $group->map($attachApprovedLeave);
        });

        $semuaSiswa = $semuaSiswa->map($attachApprovedLeave);
        
        return view('jurnal.index', compact('jadwals', 'jurnalList', 'guru', 'siswaList', 'activeJadwal', 'siswaByKelas', 'semuaSiswa'));
    }

    /**
     * API: Ambil daftar siswa berdasarkan jadwal (kelas) yang dipilih
     */
    public function getSiswaByJadwal(Request $request)
    {
        $jadwal = JadwalPelajaran::with('kelas')->find($request->schedule_id);

        if (!$jadwal) {
            return response()->json(['siswa' => [], 'kelas' => null]);
        }

        $siswaList = Siswa::where('id_kelas', $jadwal->id_kelas)
            ->where('status_utama', 'Aktif')
            ->orderBy('nama_siswa', 'asc')
            ->get(['id_siswa', 'nisn', 'nama_siswa']);

        // Cek izin siswa yang disetujui oleh Wali Kelas / BK untuk hari ini
        $today = date('Y-m-d');
        $approvedLeavesToday = LeaveRequest::where('status_approval', 'Approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get()
            ->keyBy('student_id');

        $siswaList->transform(function ($siswa) use ($approvedLeavesToday) {
            if ($approvedLeavesToday->has($siswa->id_siswa)) {
                $leave = $approvedLeavesToday->get($siswa->id_siswa);
                $isSakit = (stripos($leave->reason, 'sakit') !== false);
                $siswa->is_approved_leave = true;
                $siswa->leave_status = $isSakit ? 'Sakit' : 'Izin';
                $siswa->leave_reason = $leave->reason;
            } else {
                $siswa->is_approved_leave = false;
                $siswa->leave_status = 'Alfa';
                $siswa->leave_reason = '';
            }
            return $siswa;
        });

        return response()->json([
            'siswa' => $siswaList,
            'kelas' => $jadwal->kelas,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis, silakan login kembali!']);
        }

        $request->validate([
            'schedule_id' => 'required',
            'topic_description' => 'required',
            'photo_base64' => 'required|string',
        ], [
            'schedule_id.required' => 'Pilih sesi jadwal mengajar.',
            'topic_description.required' => 'Topik/rangkuman materi pembelajaran KBM wajib diisi.',
            'photo_base64.required' => 'Foto bukti dokumentasi KBM wajib diambil langsung dari kamera saat kegiatan KBM berlangsung!',
        ]);

        $jadwal = JadwalPelajaran::find($request->schedule_id);
        if (!$jadwal) {
            return redirect()->back()->withErrors(['schedule_id' => 'Sesi jadwal tidak ditemukan!'])->withInput();
        }

        // VALIDASI BATAS WAKTU: Maksimal 5 jam setelah jam pembelajaran selesai
        $now = \Carbon\Carbon::now();
        $mapHari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $hariIni = $mapHari[$now->dayOfWeekIso] ?? null;

        if (!empty($jadwal->jam_selesai)) {
            if ($jadwal->hari === $hariIni) {
                $waktuSelesai = \Carbon\Carbon::parse($now->toDateString() . ' ' . $jadwal->jam_selesai);
                $deadline5Jam = $waktuSelesai->copy()->addHours(5);

                if ($now->greaterThan($deadline5Jam)) {
                    $terlewat = $now->diffForHumans($deadline5Jam, true);
                    return redirect()->back()->withErrors([
                        'schedule_id' => "Pengisian jurnal ditolak! Anda telah melewati batas waktu pengisian maksimal 5 jam setelah jam pembelajaran selesai. (Jam selesai KBM: {$waktuSelesai->format('H:i')}, Batas akhir: {$deadline5Jam->format('H:i')} WIB, terlewat {$terlewat})."
                    ])->withInput();
                }
            } else {
                return redirect()->back()->withErrors([
                    'schedule_id' => "Pengisian jurnal ditolak! Jadwal ini tercatat untuk hari {$jadwal->hari}. Jurnal mengajar hanya dapat diisi pada hari pembelajaran bersangkutan dan maksimal 5 jam setelah KBM selesai."
                ])->withInput();
            }
        }

        // Simpan foto HANYA dari tangkapan kamera langsung
        $base64Image = $request->photo_base64;
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $ext = strtolower($type[1]);
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $ext = 'jpg';
            }
        } else {
            $ext = 'jpg';
        }
        $imageData = base64_decode($base64Image);
        if (!$imageData) {
            return redirect()->back()->withErrors(['photo_base64' => 'Data foto kamera tidak valid. Silakan ambil foto ulang langsung dari kamera.'])->withInput();
        }

        if (!file_exists(public_path('uploads/jurnal'))) {
            mkdir(public_path('uploads/jurnal'), 0777, true);
        }
        $filename = 'kamera_' . time() . '_' . uniqid() . '.' . $ext;
        file_put_contents(public_path('uploads/jurnal/' . $filename), $imageData);
        $photoPath = 'uploads/jurnal/' . $filename;

        DB::beginTransaction();
        try {
            // 1. Simpan Jurnal Utama Guru
            $journalId = DB::table('journals')->insertGetId([
                'schedule_id' => $request->schedule_id,
                'date' => date('Y-m-d'),
                'topic_description' => $request->topic_description,
                'photo_path' => $photoPath,
            ]);

            // 2. Simpan Data Absensi Siswa yang Tidak Hadir
            $recordedStudentIds = [];
            if ($request->has('absensi') && is_array($request->absensi)) {
                foreach ($request->absensi as $absen) {
                    // Hanya simpan siswa yang statusnya BUKAN 'Hadir'
                    if (!empty($absen['student_id']) && !empty($absen['status']) && $absen['status'] !== 'Hadir') {
                        DB::table('attendances')->insert([
                            'journal_id' => $journalId,
                            'student_id' => $absen['student_id'],
                            'status' => $absen['status'],
                            'keterangan' => $absen['keterangan'] ?? null,
                        ]);
                        $recordedStudentIds[] = (int) $absen['student_id'];
                    }
                }
            }

            // Pengaman Otomatis: Pastikan siswa di kelas ini yang sudah memiliki izin disetujui
            // oleh Wali Kelas / BK untuk hari ini otomatis terisi tidak hadir (jika belum tercatat)
            $today = date('Y-m-d');
            $jadwal = JadwalPelajaran::find($request->schedule_id);
            if ($jadwal) {
                $siswaKelasIds = Siswa::where('id_kelas', $jadwal->id_kelas)->pluck('id_siswa')->toArray();
                $autoLeaves = LeaveRequest::where('status_approval', 'Approved')
                    ->whereIn('student_id', $siswaKelasIds)
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->get();

                foreach ($autoLeaves as $al) {
                    if (!in_array((int) $al->student_id, $recordedStudentIds)) {
                        $isSakit = (stripos($al->reason, 'sakit') !== false);
                        DB::table('attendances')->insert([
                            'journal_id' => $journalId,
                            'student_id' => $al->student_id,
                            'status' => $isSakit ? 'Sakit' : 'Izin',
                            'keterangan' => 'Izin disetujui Wali Kelas/BK: ' . $al->reason,
                        ]);
                    }
                }
            }

            DB::commit();

            $absenCount = 0;
            if ($request->has('absensi') && is_array($request->absensi)) {
                $absenCount = collect($request->absensi)->filter(fn($a) => !empty($a['status']) && $a['status'] !== 'Hadir')->count();
            }

            $msg = 'Jurnal mengajar berhasil disimpan!';
            if ($absenCount > 0) {
                $msg .= ' Data absensi ' . $absenCount . ' siswa tidak hadir berhasil dicatat.';
            } else {
                $msg .= ' Semua siswa hadir (tidak ada absensi ketidakhadiran).';
            }

            return redirect()->route('jurnal.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Memperbarui Jurnal Mengajar dan Presensi Siswa yang Telah Dikirim
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis, silakan login kembali!']);
        }

        $user = Auth::user();
        $username = $user ? $user->username : session('username');
        $guru = Guru::where('nip', $username)->first();
        if (!$guru) {
            $nama = session('nama_lengkap') ?? ($user ? $user->nama_lengkap : $username);
            $guru = Guru::where('nama_guru', $nama)->first();
        }
        $userRole = session('id_role') ?? ($user ? $user->id_role : null);

        $journal = Journal::with('jadwal')->find($id);
        if (!$journal) {
            return redirect()->back()->withErrors(['error' => 'Data jurnal tidak ditemukan.']);
        }

        // Pengaman Hak Akses: Hanya guru pemilik sesi jadwal ini atau Admin/Waka Kurikulum yang berhak mengedit
        if ($userRole != 1 && $userRole != 4) {
            if (!$guru || $journal->jadwal?->id_guru != $guru->id_guru) {
                return redirect()->back()->withErrors(['error' => 'Akses ditolak! Anda hanya dapat mengedit jurnal Anda sendiri.']);
            }
        }

        $request->validate([
            'date' => 'required|date',
            'topic_description' => 'required',
            'photo_base64' => 'nullable|string',
        ], [
            'topic_description.required' => 'Topik/rangkuman materi pembelajaran wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            $photoPath = $journal->photo_path;

            // Jika guru mengambil foto bukti baru dari kamera untuk mengganti foto lama
            if ($request->filled('photo_base64')) {
                $base64Image = $request->photo_base64;
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                    $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                    $ext = strtolower($type[1]);
                    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        $ext = 'jpg';
                    }
                } else {
                    $ext = 'jpg';
                }
                $imageData = base64_decode($base64Image);
                if ($imageData !== false) {
                    if (!file_exists(public_path('uploads/jurnal'))) {
                        mkdir(public_path('uploads/jurnal'), 0777, true);
                    }
                    $filename = 'kamera_' . time() . '_' . uniqid() . '.' . $ext;
                    file_put_contents(public_path('uploads/jurnal/' . $filename), $imageData);
                    $photoPath = 'uploads/jurnal/' . $filename;
                }
            }

            // 1. Perbarui Jurnal Utama
            $journal->update([
                'date' => $request->date,
                'topic_description' => $request->topic_description,
                'photo_path' => $photoPath,
            ]);

            // 2. Sinkronkan Data Absensi Siswa
            // Hapus rekaman absensi lama pada jurnal ini terlebih dahulu
            DB::table('attendances')->where('journal_id', $journal->id)->delete();

            // Masukkan kembali absensi yang statusnya BUKAN 'Hadir'
            $absenCount = 0;
            if ($request->has('absensi') && is_array($request->absensi)) {
                foreach ($request->absensi as $absen) {
                    if (!empty($absen['student_id']) && !empty($absen['status']) && $absen['status'] !== 'Hadir') {
                        DB::table('attendances')->insert([
                            'journal_id' => $journal->id,
                            'student_id' => $absen['student_id'],
                            'status' => $absen['status'],
                            'keterangan' => $absen['keterangan'] ?? null,
                        ]);
                        $absenCount++;
                    }
                }
            }

            DB::commit();

            $msg = 'Jurnal mengajar berhasil diperbarui!';
            if ($absenCount > 0) {
                $msg .= ' Data absensi (' . $absenCount . ' siswa tidak hadir) telah diperbarui.';
            } else {
                $msg .= ' Status absensi diperbarui: Semua siswa hadir.';
            }

            return redirect()->route('jurnal.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal memperbarui jurnal: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Fitur Rekap Jurnal untuk Waka Kurikulum
     */
    public function rekapWaka()
    {
        // Pastikan yang mengakses adalah Waka Kurikulum (id_role == 4)
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Khusus Waka Kurikulum.']);
        }

        // Ambil semua data jurnal beserta relasi jadwal, kelas, mata pelajaran, data guru, dan data absensi
        $jurnalList = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.guru', 'attendances.siswa'])
            ->orderBy('date', 'desc')
            ->get();

        return view('jurnal.rekap_waka', compact('jurnalList'));
    }
}
