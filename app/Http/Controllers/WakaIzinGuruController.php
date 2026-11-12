<?php

namespace App\Http\Controllers;

use App\Models\IzinGuru;
use App\Support\Audit;
use Illuminate\Http\Request;

/** Persetujuan izin guru. HANYA Waka Kurikulum (id_role 4). */
class WakaIzinGuruController extends Controller
{
    private function cekAkses()
    {
        if (!session()->has('id_user') || (int) session('id_role') !== 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Waka Kurikulum.']);
        }
        return null;
    }

    public function index(Request $request)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }

        $filter = [
            'status' => (string) $request->query('status', 'Menunggu'),
            'q' => trim((string) $request->query('q', '')),
        ];

        $query = IzinGuru::with('guru')->orderByRaw("FIELD(status,'Menunggu','Disetujui','Ditolak')")->orderByDesc('created_at');
        if (in_array($filter['status'], ['Menunggu', 'Disetujui', 'Ditolak'], true)) {
            $query->where('status', $filter['status']);
        } else {
            $filter['status'] = 'Semua';
        }
        if ($filter['q'] !== '') {
            $q = $filter['q'];
            $query->where(function ($w) use ($q) {
                $w->where('jenis', 'like', "%$q%")
                  ->orWhere('alasan', 'like', "%$q%")
                  ->orWhereHas('guru', fn ($g) => $g->where('nama_guru', 'like', "%$q%")->orWhere('nip', 'like', "%$q%"));
            });
        }

        $izinList = $query->paginate(10)->withQueryString();
        $izinList->getCollection()->transform(function ($i) {
            $i->terdampak = $i->jadwalTerdampak();
            return $i;
        });

        $jumlah = [
            'Menunggu' => IzinGuru::where('status', 'Menunggu')->count(),
            'Disetujui' => IzinGuru::where('status', 'Disetujui')->count(),
            'Ditolak' => IzinGuru::where('status', 'Ditolak')->count(),
        ];

        return view('waka.izin-guru', compact('izinList', 'filter', 'jumlah'));
    }

    public function proses(Request $request, $id)
    {
        if ($r = $this->cekAkses()) {
            return $r;
        }

        $request->validate([
            'aksi' => 'required|in:setujui,tolak',
            'catatan' => 'nullable|string|max:500|required_if:aksi,tolak',
        ], ['catatan.required_if' => 'Catatan wajib diisi jika izin ditolak.']);

        $izin = IzinGuru::with('guru')->find($id);
        if (!$izin) {
            return back()->withErrors(['error' => 'Pengajuan tidak ditemukan.']);
        }
        if ($izin->status !== 'Menunggu') {
            return back()->withErrors(['error' => 'Pengajuan ini sudah diproses sebelumnya.']);
        }

        $baru = $request->aksi === 'setujui' ? 'Disetujui' : 'Ditolak';
        $izin->update([
            'status' => $baru,
            'catatan_waka' => $request->catatan,
            'diproses_oleh' => session('id_user'),
            'diproses_pada' => now(),
        ]);

        Audit::catat('verifikasi', 'izin_guru', $izin->id,
            $baru . ' izin mengajar ' . ($izin->guru->nama_guru ?? ('guru #' . $izin->id_guru)),
            ['status' => 'Menunggu'], ['status' => $baru, 'catatan_waka' => $request->catatan]);

        return back()->with('success', 'Izin guru berhasil ' . strtolower($baru) . '.');
    }
}
