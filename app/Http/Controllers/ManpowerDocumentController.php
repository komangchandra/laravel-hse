<?php

namespace App\Http\Controllers;

use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Services\FileAntivirusScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManpowerDocumentController extends Controller
{
    public function __construct(private readonly FileAntivirusScanner $antivirus) {}

    public function store(Request $request, Manpower $manpower): RedirectResponse
    {
        $this->authorize('create', [ManpowerDocument::class, $manpower]);
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(ManpowerDocument::TYPES))],
            'document_number' => ['required', 'string', 'max:100'],
            'issued_at' => ['required', 'date', 'before_or_equal:today'],
            'file' => ['required', File::types(['pdf'])->max(2 * 1024)],
        ]);

        $file = $request->file('file');
        $this->antivirus->scan($file);
        unset($validated['file']);

        DB::transaction(function () use ($request, $manpower, $validated, $file) {
            Manpower::whereKey($manpower->id)->lockForUpdate()->firstOrFail();
            $version = (int) $manpower->allDocuments()->where('type', $validated['type'])->max('version') + 1;
            $path = $file->store("manpowers/{$manpower->id}/documents/{$validated['type']}", 'local');

            ManpowerDocument::create([
                ...$validated,
                'manpower_id' => $manpower->id,
                'file_path' => $path,
                'original_name' => $this->safeOriginalName($file->getClientOriginalName()),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'file_size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'version' => $version,
                'uploaded_by' => $request->user()->id,
                'verification_status' => 'pending',
            ]);
        });

        return back()->with('success', 'Dokumen berhasil ditambahkan sebagai versi baru.');
    }

    public function download(Manpower $manpower, ManpowerDocument $document): StreamedResponse
    {
        abort_unless($document->manpower_id === $manpower->id, 404);
        $this->authorize('view', $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function verify(Request $request, Manpower $manpower, ManpowerDocument $document): RedirectResponse
    {
        abort_unless($document->manpower_id === $manpower->id, 404);
        $this->authorize('verify', $document);
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'rejected'])],
            'verification_note' => [Rule::requiredIf($request->input('verification_status') === 'rejected'), 'nullable', 'string', 'max:1000'],
        ]);

        $document->update([
            ...$validated,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Status verifikasi dokumen berhasil diperbarui.');
    }

    public function destroy(Manpower $manpower, ManpowerDocument $document): RedirectResponse
    {
        abort_unless($document->manpower_id === $manpower->id, 404);
        $this->authorize('delete', $document);
        $document->delete();

        return back()->with('success', 'Versi dokumen diarsipkan. File historis tetap disimpan.');
    }

    private function safeOriginalName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', basename($name)) ?: 'dokumen';

        return mb_substr($name, 0, 200);
    }
}
