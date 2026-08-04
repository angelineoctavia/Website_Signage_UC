<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PlaylistDetail;

class Playlist extends Model
{
    use HasFactory;

    protected $table = 'playlists';
    protected $primaryKey = 'playlist_id';
    
    // Tambahkan di sini
    public $timestamps = false; 

    protected $fillable = [
        'playlist_start_date',
        'playlist_end_date',
        'playlist_duration_formatted',
        'status_del'
    ];

    public function details()
    {
        return $this->hasMany(PlaylistDetail::class, 'playlist_id', 'playlist_id');
    }
}