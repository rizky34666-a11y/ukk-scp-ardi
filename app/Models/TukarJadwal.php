<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TukarJadwal extends Model
{
    protected $table = 'tukar_jadwal';

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'batas_waktu' => 'datetime',
        'diproses_pada' => 'datetime',
        'perubahan' => 'array',
    ];

    public function jadwalPemohon()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'id_jadwal_pemohon', 'id_jadwal');
    }

    public function jadwalTujuan()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'id_jadwal_tujuan', 'id_jadwal');
    }

    public function guruPemohon()
    {
        return $this->belongsTo(Guru::class, 'id_guru_pemohon', 'id_guru');
    }

    public function guruTujuan()
    {
        return $this->belongsTo(Guru::class, 'id_guru_tujuan', 'id_guru');
    }

    public function ruanganDiminta()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_diminta', 'id_ruangan');
    }
}
