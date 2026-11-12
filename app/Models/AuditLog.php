<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Catatan aktivitas yang tidak boleh diubah atau dihapus lewat aplikasi.
 */
class AuditLog extends Model
{
    public $timestamps = false;
    protected $table = 'audit_logs';
    protected $guarded = [];
    protected $casts = [
        'data_lama'  => 'array',
        'data_baru'  => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Audit log tidak boleh diubah.');
        });
        static::deleting(function () {
            throw new LogicException('Audit log tidak boleh dihapus.');
        });
    }
}
