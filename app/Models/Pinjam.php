<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pinjam extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'loan_date', 'return_date', 'actual_return_date', 'status','keterangan_peminjam','keterangan_penyetuju', 'status_warek'
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke detail pinjam
    public function details()
    {
        return $this->hasMany(PinjamDetail::class);
    }
}
