<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $table = 'contents';
    protected $primaryKey = 'contents_id';
    public $timestamps = false;

    protected $fillable = [
        'users_id',
        'content_title',
        'content_file_path_url',
        'content_category',
        'content_type',
        'content_duration',
        'content_start_date',
        'content_end_date',
        'content_status',
        'status_del'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'users_id');
    }
}