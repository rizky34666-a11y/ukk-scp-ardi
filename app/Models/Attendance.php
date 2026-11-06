<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';
    protected $guarded = [];
    public $timestamps = false;

    /**
     * Relasi: Absensi milik satu Jurnal
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id', 'id');
    }

    /**
     * Relasi: Absensi milik satu Siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'student_id', 'id_siswa');
    }
}