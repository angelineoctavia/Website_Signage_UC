<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignageStatus extends Model
{
    use HasFactory;

    // Menyesuaikan dengan nama tabel di database kamu
    protected $table = 'signage_status';

    // Menyesuaikan primary key
    protected $primaryKey = 'status_id';

    // Kolom yang boleh diisi (mass assignable)
    protected $fillable = [
        'playlist_id',
        'users_id',
        'status_updated_by',
        'status_updated_at',
    ];

    // Jika tabel kamu menggunakan timestamps bawaan (created_at & updated_at)
    public $timestamps = false;

    // Relasi ke tabel Playlist
    public function playlist()
    {
        return $this->belongsTo(Playlist::class, 'playlist_id', 'playlist_id');
    }

    // Relasi ke tabel User (berdasarkan status_updated_by yang mereferensikan users_id)
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'status_updated_by', 'users_id');
    }
}