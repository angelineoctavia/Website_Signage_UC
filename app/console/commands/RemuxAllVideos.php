<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Jobs\RemuxVideoForWeb;

class RemuxAllVideos extends Command
{
    protected $signature = 'remux:all';
    protected $description = 'Remux ulang semua video lama di storage/uploads biar faststart & bisa diputar TV';

    public function handle()
    {
        $files = Storage::disk('public')->files('uploads');

        $videoExtensions = ['mp4', 'mov', 'avi'];
        $count = 0;

        foreach ($files as $relativePath) {
            $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

            if (in_array($ext, $videoExtensions)) {
                $this->info("Remux: {$relativePath}");
                RemuxVideoForWeb::dispatch($relativePath);
                $count++;
            }
        }

        $this->info("Selesai. Total {$count} video diproses.");
    }
}