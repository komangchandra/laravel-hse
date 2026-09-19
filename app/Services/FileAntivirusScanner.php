<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Throwable;

class FileAntivirusScanner
{
    public function scan(UploadedFile $file): void
    {
        $binary = config('filesystems.antivirus.clamav_binary');
        if (! is_string($binary) || $binary === '') {
            return;
        }

        try {
            $process = new Process([$binary, '--no-summary', $file->getRealPath()]);
            $process->setTimeout(30)->run();
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'File tidak dapat dipindai antivirus. Periksa konfigurasi server.']);
        }

        if ($process->getExitCode() === 1) {
            throw ValidationException::withMessages(['file' => 'File ditolak karena terdeteksi berbahaya.']);
        }

        if (! $process->isSuccessful()) {
            throw ValidationException::withMessages(['file' => 'File tidak dapat dipindai antivirus. Silakan coba lagi.']);
        }
    }
}
