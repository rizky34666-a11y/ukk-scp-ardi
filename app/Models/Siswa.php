<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';
    protected $primaryKey = 'id_siswa';
    public $timestamps = false; 

    protected $fillable = [
        'nisn',
        'nama_siswa',
        'id_kelas',
        'status_utama',
    ];

    /**
     * Relasi: Siswa milik satu Kelas
     */
    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    /**
     * Relasi: Siswa memiliki banyak riwayat absensi
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id', 'id_siswa');
    }

    /**
     * Relasi: Siswa memiliki banyak permohonan izin
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'student_id', 'id_siswa');
    }
}