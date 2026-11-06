<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\Kelas;
use App\Models\Siswa;
use Carbon\Carbon;

class RekapAbsensiController extends Controller
{
    /**
     * Tampilan Rekap Absensi Siswa Tidak Berangkat (Khusus BK dan Waka)
     * Menggabungkan:
     * 1. Siswa yang mengajukan izin kepada Wali Kelas / Guru BK (LeaveRequest)
     * 2. Siswa yang Alfa pada Rekapitulasi Jurnal Mengajar Guru (Attendance)
     * Dilengkapi:
     * - Filter Periode Cepat (Hari Ini, Minggu Ini, Bulan Ini, Semua)
     * - Deteksi Notifikasi Alfa Berturut-turut (Consecutive Absence)
     */
    public function index(Request $request)
    {
        // 1. Pengaman Hak Akses: Khusus Waka Kurikulum (4), Guru BK (7), Wali Kelas (3), atau Admin (1)
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $userRole = session('id_role');
        if (!in_array($userRole, [4, 7, 3, 1])) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman rekap absensi ini khusus untuk Guru BK dan Waka Kurikulum.']);
        }

        // 2. Filter Periode Cepat & Rentang Tanggal
        $period = $request->query('period', '');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($period === 'today') {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($period === 'this_week') {
            $startDate = Carbon::now()->startOfWeek()->toDateString();
            $endDate = Carbon::now()->endOfWeek()->toDateString();
        } elseif ($period === 'this_month') {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        } elseif ($period === 'all') {
            $startDate = null;
            $endDate = null;
        } else {
            // Deteksi jika tanggal manual cocok dengan periode cepat
            if ($startDate && $endDate) {
                if ($startDate === Carbon::today()->toDateString() && $endDate === Carbon::today()->toDateString()) {
                    $period = 'today';
                } elseif ($startDate === Carbon::now()->startOfWeek()->toDateString() && $endDate === Carbon::now()->endOfWeek()->toDateString()) {
                    $period = 'this_week';
                } elseif ($startDate === Carbon::now()->startOfMonth()->toDateString() && $endDate === Carbon::now()->endOfMonth()->toDateString()) {
                    $period = 'this_month';
                } else {
                    $period = 'custom';
                }
            } else {
                $period = 'all';
            }
        }

        $filterKelas = $request->query('kelas', 'all');
        $filterStatus = $request->query('status', 'all'); // 'all', 'Alfa', 'Approved', 'Pending', 'Rejected'
        $search = trim($request->query('search', ''));

        // 3. Ambil seluruh data master kelas untuk filter navigasi
        $daftarKelas = Kelas::with('jurusan')->orderBy('tingkat')->orderBy('id_kelas')->get();

        $kelasGroups = [];

        // Helper untuk inisialisasi kelas
        $ensureKelasGroup = function ($kelasId) use (&$kelasGroups, $daftarKelas) {
            if (!isset($kelasGroups[$kelasId])) {
                $kelasModel = $daftarKelas->firstWhere('id_kelas', $kelasId);
                $kelasGroups[$kelasId] = [
                    'id_kelas' => $kelasId,
                    'nama_kelas' => $kelasModel ? (trim(($kelasModel->tingkat ?? '') . ' ' . ($kelasModel->jurusan?->nama_jurusan ?? '')) ?: $kelasId) : ($kelasId === 'Lainnya' ? 'Kelas Belum Terdata' : 'Kelas ' . $kelasId),
                    'tingkat' => $kelasModel?->tingkat ?? '',
                    'jurusan' => $kelasModel?->jurusan?->nama_jurusan ?? '',
                    'total_tidak_hadir' => 0,
                    'total_alfa' => 0,
                    'total_izin' => 0,
                    'total_sakit' => 0,
                    'total_hari_izin' => 0,
                    'total_siswa_consecutive_alfa' => 0,
                    'siswa' => [],
                ];
            }
        };

        // Helper untuk inisialisasi siswa di kelas
        $ensureSiswa = function ($kelasId, $studentId, $student) use (&$kelasGroups) {
            if (!isset($kelasGroups[$kelasId]['siswa'][$studentId])) {
                $kelasGroups[$kelasId]['siswa'][$studentId] = [
                    'id_siswa' => $studentId,
                    'nama_siswa' => $student?->nama_siswa ?? 'Siswa Tidak Ditemukan',
                    'nisn' => $student?->nisn ?? '-',
                    'id_kelas' => $kelasId,
                    'total_tidak_hadir' => 0,
                    'alfa_count' => 0,
                    'izin_count' => 0,
                    'sakit_count' => 0,
                    'approved_count' => 0,
                    'pending_count' => 0,
                    'rejected_count' => 0,
                    'max_consecutive_alfa' => 0,
                    'has_consecutive_alfa' => false,
                    'riwayat' => [],
                ];
            }
        };

        // 4. ALUR A: Data Siswa yang Mengajukan Izin ke Wali Kelas / BK (LeaveRequest)
        if ($filterStatus !== 'Alfa') {
            $leaveQuery = LeaveRequest::with(['student.kelas.jurusan', 'verifier']);

            if (in_array($filterStatus, ['Approved', 'Pending', 'Rejected'])) {
                $leaveQuery->where('status_approval', $filterStatus);
            }

            if ($startDate) {
                $leaveQuery->whereDate('end_date', '>=', $startDate);
            }
            if ($endDate) {
                $leaveQuery->whereDate('start_date', '<=', $endDate);
            }

            if (!empty($search)) {
                $leaveQuery->where(function ($q) use ($search) {
                    $q->whereHas('student', function ($sq) use ($search) {
                        $sq->where('nama_siswa', 'like', "%{$search}%")
                          ->orWhere('nisn', 'like', "%{$search}%");
                    })->orWhere('reason', 'like', "%{$search}%");
                });
            }

            $leaves = $leaveQuery->orderBy('created_at', 'desc')->get();

            foreach ($leaves as $leave) {
                $student = $leave->student;
                $kelasId = $student?->id_kelas ?? 'Lainnya';
                $studentId = $leave->student_id;

                if ($filterKelas !== 'all' && $kelasId !== $filterKelas) {
                    continue;
                }

                $ensureKelasGroup($kelasId);
                $ensureSiswa($kelasId, $studentId, $student);

                $start = Carbon::parse($leave->start_date);
                $end = Carbon::parse($leave->end_date);
                $durasiHari = $end->greaterThanOrEqualTo($start) ? $start->diffInDays($end) + 1 : 1;

                $isSakit = (stripos($leave->reason, 'sakit') !== false);

                // Counter Kelas
                $kelasGroups[$kelasId]['total_tidak_hadir']++;
                $kelasGroups[$kelasId]['total_hari_izin'] += $durasiHari;
                if ($isSakit) {
                    $kelasGroups[$kelasId]['total_sakit']++;
                } else {
                    $kelasGroups[$kelasId]['total_izin']++;
                }

                // Counter Siswa
                $kelasGroups[$kelasId]['siswa'][$studentId]['total_tidak_hadir']++;
                if ($leave->status_approval === 'Approved') {
                    $kelasGroups[$kelasId]['siswa'][$studentId]['approved_count']++;
                } elseif ($leave->status_approval === 'Pending') {
                    $kelasGroups[$kelasId]['siswa'][$studentId]['pending_count']++;
                } else {
                    $kelasGroups[$kelasId]['siswa'][$studentId]['rejected_count']++;
                }

                if ($isSakit) {
                    $kelasGroups[$kelasId]['siswa'][$studentId]['sakit_count']++;
                } else {
                    $kelasGroups[$kelasId]['siswa'][$studentId]['izin_count']++;
                }

                // Riwayat
                $kelasGroups[$kelasId]['siswa'][$studentId]['riwayat'][] = [
                    'id' => 'leave_' . $leave->id,
                    'tipe' => 'Surat Izin (Wali/BK)',
                    'status' => $leave->status_approval === 'Approved' ? ($isSakit ? 'Sakit' : 'Izin') : 'Izin (Pending)',
                    'start_date' => $leave->start_date ? Carbon::parse($leave->start_date)->format('Y-m-d') : null,
                    'end_date' => $leave->end_date ? Carbon::parse($leave->end_date)->format('Y-m-d') : null,
                    'durasi_hari' => $durasiHari,
                    'alasan' => $leave->reason,
                    'mapel' => '-',
                    'guru' => '-',
                    'document_path' => $leave->document_path,
                    'status_approval' => $leave->status_approval,
                    'verified_by' => $leave->verifier?->nama_lengkap ?? ($leave->status_approval === 'Approved' ? 'Wali Kelas / BK' : 'Belum diverifikasi'),
                    'created_at' => $leave->created_at ? Carbon::parse($leave->created_at)->format('Y-m-d H:i') : null,
                ];
            }
        }

        // 5. ALUR B: Data Siswa yang Alfa pada Rekapitulasi Jurnal Mengajar Guru (Attendance)
        if ($filterStatus === 'all' || $filterStatus === 'Alfa') {
            $alfaQuery = Attendance::with([
                'siswa.kelas.jurusan',
                'journal.jadwal.kelas.jurusan',
                'journal.jadwal.mataPelajaran',
                'journal.jadwal.guru'
            ])->where('status', 'Alfa');

            if ($startDate) {
                $alfaQuery->whereHas('journal', function ($q) use ($startDate) {
                    $q->whereDate('date', '>=', $startDate);
                });
            }
            if ($endDate) {
                $alfaQuery->whereHas('journal', function ($q) use ($endDate) {
                    $q->whereDate('date', '<=', $endDate);
                });
            }

            if (!empty($search)) {
                $alfaQuery->where(function ($q) use ($search) {
                    $q->whereHas('siswa', function ($sq) use ($search) {
                        $sq->where('nama_siswa', 'like', "%{$search}%")
                          ->orWhere('nisn', 'like', "%{$search}%");
                    })->orWhere('keterangan', 'like', "%{$search}%");
                });
            }

            $alfaRecords = $alfaQuery->get();

            foreach ($alfaRecords as $att) {
                $student = $att->siswa;
                $kelasId = $student?->id_kelas ?? $att->journal?->jadwal?->id_kelas ?? 'Lainnya';
                $studentId = $att->student_id;

                if ($filterKelas !== 'all' && $kelasId !== $filterKelas) {
                    continue;
                }

                $ensureKelasGroup($kelasId);
                $ensureSiswa($kelasId, $studentId, $student);

                $tanggalKbm = $att->journal?->date ? Carbon::parse($att->journal->date)->format('Y-m-d') : null;
                $namaMapel = $att->journal?->jadwal?->mataPelajaran?->nama_mapel ?? 'Mapel';
                $namaGuru = $att->journal?->jadwal?->guru?->nama_guru ?? 'Guru Pengampu';
                $catatanGuru = !empty($att->keterangan) ? $att->keterangan : 'Tanpa keterangan (Alfa di Jurnal KBM)';

                // Counter Kelas
                $kelasGroups[$kelasId]['total_tidak_hadir']++;
                $kelasGroups[$kelasId]['total_alfa']++;

                // Counter Siswa
                $kelasGroups[$kelasId]['siswa'][$studentId]['total_tidak_hadir']++;
                $kelasGroups[$kelasId]['siswa'][$studentId]['alfa_count']++;

                // Riwayat Alfa dari Jurnal Guru
                $kelasGroups[$kelasId]['siswa'][$studentId]['riwayat'][] = [
                    'id' => 'alfa_' . $att->id,
                    'tipe' => 'Alfa (Jurnal Guru)',
                    'status' => 'Alfa',
                    'start_date' => $tanggalKbm,
                    'end_date' => $tanggalKbm,
                    'durasi_hari' => 1,
                    'alasan' => "Alfa pada KBM {$namaMapel} — Catatan Guru: {$catatanGuru}",
                    'mapel' => $namaMapel,
                    'guru' => $namaGuru,
                    'document_path' => null,
                    'status_approval' => 'Tercatat Alfa',
                    'verified_by' => "Jurnal Guru: {$namaGuru}",
                    'created_at' => $tanggalKbm,
                ];
            }
        }

        // 6. Analisis Alfa Berturut-turut (Consecutive Absence) & Status Evaluasi BK
        $totalSiswaConsecutiveAlfa = 0;

        foreach ($kelasGroups as $kId => &$kData) {
            foreach ($kData['siswa'] as &$sData) {
                // Urutkan riwayat ketidakhadiran dari tanggal terbaru
                usort($sData['riwayat'], function ($a, $b) {
                    return strcmp($b['start_date'] ?? '', $a['start_date'] ?? '');
                });

                // --- ALGORITMA DETEKSI ALFA BERTURUT-TURUT ---
                $alfaDates = [];
                foreach ($sData['riwayat'] as $r) {
                    if ($r['status'] === 'Alfa' && !empty($r['start_date'])) {
                        $alfaDates[] = $r['start_date'];
                    }
                }
                $uniqueAlfaDates = array_values(array_unique($alfaDates));
                sort($uniqueAlfaDates); // Urutkan kronologis dari tanggal terlama ke terbaru

                $maxConsecutive = 0;
                if (count($uniqueAlfaDates) > 0) {
                    $maxConsecutive = 1;
                    $currentStreak = 1;
                    for ($i = 0; $i < count($uniqueAlfaDates) - 1; $i++) {
                        $d1 = Carbon::parse($uniqueAlfaDates[$i]);
                        $d2 = Carbon::parse($uniqueAlfaDates[$i + 1]);
                        $diff = $d1->diffInDays($d2);

                        // Berturut-turut kalender (1 hari) ATAU hari sekolah (Jumat ke Senin = diff <= 3 hari)
                        if ($diff == 1 || ($diff <= 3 && $d1->isFriday() && $d2->isMonday())) {
                            $currentStreak++;
                            if ($currentStreak > $maxConsecutive) {
                                $maxConsecutive = $currentStreak;
                            }
                        } else {
                            $currentStreak = 1;
                        }
                    }
                }

                $sData['max_consecutive_alfa'] = $maxConsecutive;
                $sData['has_consecutive_alfa'] = ($maxConsecutive >= 2);

                if ($sData['has_consecutive_alfa']) {
                    $kData['total_siswa_consecutive_alfa']++;
                    $totalSiswaConsecutiveAlfa++;
                }

                // Status Tindak Lanjut BK:
                if ($sData['max_consecutive_alfa'] >= 3) {
                    $sData['kategori_bk'] = 'DARURAT: Alfa ' . $sData['max_consecutive_alfa'] . ' Hari Berturut-turut (Home Visit BK)';
                    $sData['warna_bk'] = 'danger';
                } elseif ($sData['max_consecutive_alfa'] == 2) {
                    $sData['kategori_bk'] = 'PERINGATAN: Alfa 2 Hari Berturut-turut';
                    $sData['warna_bk'] = 'danger';
                } elseif ($sData['alfa_count'] >= 3 || $sData['total_tidak_hadir'] >= 5) {
                    $sData['kategori_bk'] = 'Panggilan Ortu / Bimbingan Khusus BK';
                    $sData['warna_bk'] = 'danger';
                } elseif ($sData['alfa_count'] >= 1 || $sData['total_tidak_hadir'] >= 3) {
                    $sData['kategori_bk'] = 'Perhatian Khusus BK';
                    $sData['warna_bk'] = 'warning';
                } else {
                    $sData['kategori_bk'] = 'Pemantauan Normal';
                    $sData['warna_bk'] = 'info';
                }
            }
            unset($sData);

            // Urutkan siswa: Prioritaskan yang Alfa berturut-turut paling banyak di urutan teratas
            uasort($kData['siswa'], function ($a, $b) {
                if ($b['max_consecutive_alfa'] !== $a['max_consecutive_alfa']) {
                    return $b['max_consecutive_alfa'] <=> $a['max_consecutive_alfa'];
                }
                if ($b['alfa_count'] !== $a['alfa_count']) {
                    return $b['alfa_count'] <=> $a['alfa_count'];
                }
                return $b['total_tidak_hadir'] <=> $a['total_tidak_hadir'];
            });
        }
        unset($kData);

        // Urutkan kelompok kelas
        ksort($kelasGroups);

        // 7. Hitung Agregat Keseluruhan untuk Header Card Statistik
        $totalSiswaUnik = 0;
        $totalSiswaAlfa = 0;
        $totalFrekuensiAlfa = 0;
        $totalFrekuensiIzinSakit = 0;
        $totalTotalTidakMasuk = 0;
        $totalPerluTindakanBK = 0;

        $siswaUnikIds = [];
        foreach ($kelasGroups as $kData) {
            $totalTotalTidakMasuk += $kData['total_tidak_hadir'];
            $totalFrekuensiAlfa += $kData['total_alfa'];
            $totalFrekuensiIzinSakit += ($kData['total_izin'] + $kData['total_sakit']);

            foreach ($kData['siswa'] as $sData) {
                if (!in_array($sData['id_siswa'], $siswaUnikIds)) {
                    $siswaUnikIds[] = $sData['id_siswa'];
                    if ($sData['alfa_count'] > 0) {
                        $totalSiswaAlfa++;
                    }
                }
                if ($sData['warna_bk'] === 'danger' || $sData['warna_bk'] === 'warning') {
                    $totalPerluTindakanBK++;
                }
            }
        }
        $totalSiswaUnik = count($siswaUnikIds);

        return view('rekap_absensi.index', compact(
            'kelasGroups',
            'daftarKelas',
            'filterKelas',
            'filterStatus',
            'search',
            'period',
            'startDate',
            'endDate',
            'totalSiswaUnik',
            'totalSiswaAlfa',
            'totalSiswaConsecutiveAlfa',
            'totalFrekuensiAlfa',
            'totalFrekuensiIzinSakit',
            'totalTotalTidakMasuk',
            'totalPerluTindakanBK'
        ));
    }
}
