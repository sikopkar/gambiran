<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anggota extends Model
{
    protected $table = 'anggota';
    protected $primaryKey = 'id_anggota';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id_anggota',
        'nama',
        'alamat',
        'kontak',
        'status',
        'jenis_anggota',
        'tanggal_daftar'
    ];

    public function pinjamans()
    {
        return $this->hasMany(Pinjaman::class, 'id_anggota', 'id_anggota');
    }

    public function simpanan()
    {
        return $this->hasMany(Simpanan::class, 'id_anggota', 'id_anggota');
    }
}
