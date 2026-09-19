<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->move('public', 'local');
    }

    public function down(): void
    {
        $this->move('local', 'public');
    }

    private function move(string $from, string $to): void
    {
        $paths = DB::table('manpowers')->whereNotNull('photo_path')->pluck('photo_path')
            ->merge(DB::table('manpowers')->whereNotNull('document_path')->pluck('document_path'))
            ->merge(DB::table('questions')->whereNotNull('photo_path')->pluck('photo_path'))
            ->filter()->unique();

        foreach ($paths as $path) {
            if (! Storage::disk($from)->exists($path)) {
                continue;
            }

            if (! Storage::disk($to)->exists($path)) {
                $stream = Storage::disk($from)->readStream($path);
                if ($stream === false) {
                    continue;
                }
                Storage::disk($to)->put($path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (Storage::disk($to)->exists($path)) {
                Storage::disk($from)->delete($path);
            }
        }
    }
};
