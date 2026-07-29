<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Content;

class PlaylistDetail extends Model
{
    use HasFactory;

    protected $table = 'playlist_details';
    protected $primaryKey = 'playlist_detail_id';
    public $timestamps = false;

    protected $fillable = [
        'contents_id',
        'playlist_id',
        'playlist_order'
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'contents_id', 'contents_id');
    }

    public function playlist()
    {
        return $this->belongsTo(Playlist::class, 'playlist_id', 'playlist_id');
    }
}