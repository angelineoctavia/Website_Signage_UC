<?php
// app/Jobs/RemuxVideoForWeb.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class RemuxVideoForWeb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const FFMPEG_BIN = 'C:\\ffmpeg-9.0.1-essentials_build\\bin\\ffmpeg.exe';

    public function __construct(private string $relativePath)
    {
    }

    public function handle(): void
    {
        Log::info("REMUX JOB STARTED: {$this->relativePath}");

        $fullPath = Storage::disk('public')->path($this->relativePath);
        $tempOutput = $fullPath . '.remuxed.mp4';

        if (!file_exists($fullPath)) {
            Log::warning("File asli gak ketemu: {$fullPath}");
            return;
        }

        // Command MENTAH, presisi: cuma pindahin moov atom ke depan (faststart),
        // stream video & audio di-copy APA ADANYA, gak ada re-encode sama sekali.
        $process = new Process([
            self::FFMPEG_BIN,
            '-y',                  // timpa tempOutput kalau udah ada dari percobaan sebelumnya
            '-i', $fullPath,
            '-c', 'copy',
            '-movflags', '+faststart',
            $tempOutput,
        ]);
        $process->setTimeout(3600);

        try {
            $process->run();

            Log::info("Exit code: " . $process->getExitCode());
            if (!$process->isSuccessful()) {
                Log::warning("ffmpeg gagal untuk {$this->relativePath}. stderr: " . $process->getErrorOutput());
                if (file_exists($tempOutput)) @unlink($tempOutput);
                return;
            }

            if (file_exists($tempOutput) && filesize($tempOutput) > 0) {
                rename($tempOutput, $fullPath);
                Log::info("Remux sukses (raw copy): {$this->relativePath}");
            } else {
                Log::warning("Temp output kosong/gak kebentuk untuk: {$this->relativePath}");
            }
        } catch (\Throwable $e) {
            Log::warning("Remux exception untuk {$this->relativePath}: " . $e->getMessage());
            if (file_exists($tempOutput)) @unlink($tempOutput);
        }
    }
}