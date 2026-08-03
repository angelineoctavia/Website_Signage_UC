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

    // Resolusi content_file_path_url jadi URL yang bisa dipakai <video>/<img>
    public static function resolveFileUrl(string $filePath, ?string $extension = null)
    {
        if (empty($filePath)) {
            return '';
        }

        // Data lama: udah full URL (http/https) — pakai apa adanya
        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return $filePath;
        }

        // Data lama: path lokal (ada slash, contoh: uploads/contents/xxx.mp4)
        if (str_contains($filePath, '/')) {
            return asset('storage/' . ltrim($filePath, '/'));
        }

        // Format baru: cuma file ID Drive (string alfanumerik tanpa slash)
        return route('drive.stream', ['fileId' => $filePath, 'ext' => $extension ?? 'mp4']);
    }
}
