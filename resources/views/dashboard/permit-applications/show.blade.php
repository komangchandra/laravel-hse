@extends('layouts.admin')
@section('title', 'Detail Pengajuan')
@section('content')
@php
    $draftStatuses = [App\Enums\PermitApplicationStatus::Draft, App\Enums\PermitApplicationStatus::HseRevisionRequired, App\Enums\PermitApplicationStatus::KttRevisionRequired];
    $showSnapshot = $application->submitted_snapshot && !in_array($application->status, $draftStatuses, true);
    $snapshot = $showSnapshot ? $application->submitted_snapshot : [];
    $formData = $showSnapshot ? ($snapshot['application_data'] ?? []) : [
        'site_name' => $application->site_name,
        'assignment_position' => $application->assignment_position,
        'assignment_department' => $application->assignment_department,
        'planned_start_date' => $application->planned_start_date?->toDateString(),
        'requested_valid_until' => $application->requested_valid_until?->toDateString(),
        'applicant_notes' => $application->applicant_notes,
    ];
    $areas = $showSnapshot ? collect($snapshot['access_areas'] ?? [])->pluck('name') : $application->accessAreas->pluck('name');
    $categories = $showSnapshot ? collect($snapshot['categories'] ?? []) : $application->categories->map(fn ($category) => [
        'name' => $category->name,
        'level' => $category->pivot->level,
        'restrictions' => $category->pivot->restrictions,
        'supervisor_name' => $category->pivot->supervisor_name,
        'activity_start_date' => $category->pivot->activity_start_date,
        'activity_end_date' => $category->pivot->activity_end_date,
    ]);
    $documents = $application->documentSnapshots->where('submission_version', $application->submission_version);
    $hseApprovedCurrentSubmission = $application->reviews
        ->where('stage', 'hse')
        ->where('decision', 'approved')
        ->where('submission_version', $application->submission_version)
        ->isNotEmpty();
    $latestReject = $application->reviews->where('decision', 'rejected')->sortByDesc('reviewed_at')->first();
@endphp

<div class="d-flex justify-content-between align-items-start mb-4">
    <div><h4 class="fw-bold mb-1">{{ $application->application_number }}</h4><span class="badge bg-primary">{{ ucwords(str_replace('_', ' ', $application->status->value)) }}</span><span class="text-muted ms-2">Versi {{ $application->version }} / submission {{ $application->submission_version }}</span></div>
    <div class="d-flex gap-2">@can('update', $application)<a href="{{ route('dashboard.permit-applications.edit', $application) }}" class="btn btn-outline-primary">Perbaiki Pengajuan</a>@endcan<a href="{{ route('dashboard.permit-applications.index', auth()->user()->hasRole('ktt') ? ['queue' => 'ktt'] : (auth()->user()->hasRole('hse_owner') ? ['queue' => 'review'] : [])) }}" class="btn btn-outline-secondary">Kembali</a></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if($latestReject)<div class="alert alert-warning"><strong>Catatan penolakan {{ strtoupper($latestReject->stage) }}:</strong> {{ $latestReject->notes }}</div>@endif
@if($showSnapshot)<div class="alert alert-info"><strong>Mode pemeriksaan snapshot.</strong> Data di bawah adalah data yang dibekukan saat submission {{ $application->submission_version }}, bukan perubahan profil setelah submit.</div>@endif
@if($application->manpower->trashed())<div class="alert alert-secondary">Tenaga kerja saat ini telah diarsipkan. Pengajuan tetap ditampilkan menggunakan snapshot saat submit.</div>@endif
@if($application->issuance)<div class="alert alert-success d-flex justify-content-between align-items-center"><div><strong>Permit telah diterbitkan:</strong> {{ $application->issuance->mine_permit_number }}@if($application->issuance->simpol_number) / {{ $application->issuance->simpol_number }}@endif</div><a class="btn btn-sm btn-success" href="{{ route('dashboard.permit-cards.show', $application->issuance) }}">Lihat Kartu &amp; Barcode</a></div>@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <h5 class="fw-bold">{{ $showSnapshot ? 'Snapshot form pengajuan' : 'Data draft terkini' }}</h5>
            <dl class="row mb-0">
                <dt class="col-sm-4">Tenaga kerja</dt><dd class="col-sm-8">{{ $showSnapshot ? ($snapshot['name'] ?? '-') : $application->manpower->name }} / {{ $showSnapshot ? ($snapshot['nik'] ?? '-') : $application->manpower->nik }}</dd>
                <dt class="col-sm-4">Organisasi tenaga kerja</dt><dd class="col-sm-8">{{ $showSnapshot ? ($snapshot['partner_name'] ?? '-') : $application->partner->legal_name }}</dd>
                <dt class="col-sm-4">Pengaju</dt><dd class="col-sm-8">{{ $application->creator?->name ?? '-' }} ({{ $application->partner->short_name }})</dd>
                <dt class="col-sm-4">Jenis</dt><dd class="col-sm-8">{{ $application->type->value === 'mine_permit_only' ? 'Mine Permit only' : 'Mine Permit + SIMPER' }}</dd>
                <dt class="col-sm-4">Posisi/departemen asal</dt><dd class="col-sm-8">{{ $showSnapshot ? ($snapshot['position'] ?? '-') : $application->manpower->position }} / {{ $showSnapshot ? ($snapshot['department'] ?? '-') : $application->manpower->department }}</dd>
                <dt class="col-sm-4">Site</dt><dd class="col-sm-8">{{ $formData['site_name'] ?? '-' }}</dd>
                <dt class="col-sm-4">Penugasan</dt><dd class="col-sm-8">{{ $formData['assignment_position'] ?? '-' }} / {{ $formData['assignment_department'] ?? '-' }}</dd>
                <dt class="col-sm-4">Rencana mulai</dt><dd class="col-sm-8">{{ !empty($formData['planned_start_date']) ? \Illuminate\Support\Carbon::parse($formData['planned_start_date'])->format('d-m-Y') : '-' }}</dd>
                <dt class="col-sm-4">Masa berlaku permit</dt><dd class="col-sm-8">@if($application->submitted_at){{ $application->submitted_at->format('d-m-Y') }} s.d. {{ $application->submitted_at->copy()->addYear()->format('d-m-Y') }}@else Otomatis 1 tahun sejak pengajuan dikirim @endif</dd>
                <dt class="col-sm-4">Area akses</dt><dd class="col-sm-8">{{ $areas->join(', ') ?: '-' }}</dd>
                <dt class="col-sm-4">Catatan pengaju</dt><dd class="col-sm-8">{{ $formData['applicant_notes'] ?? '-' }}</dd>
                @if($showSnapshot)<dt class="col-sm-4">Waktu submit</dt><dd class="col-sm-8">{{ $application->submitted_at?->format('d-m-Y H:i') ?? '-' }}</dd>@endif
            </dl>
        </div></div>

        @if($application->submitted_snapshot && in_array($application->status, [App\Enums\PermitApplicationStatus::HseRevisionRequired, App\Enums\PermitApplicationStatus::KttRevisionRequired], true))
            @php($previous = $application->submitted_snapshot['application_data'] ?? [])
            <div class="card border-warning shadow-sm mb-4"><div class="card-body p-4"><h5 class="fw-bold">Snapshot submission sebelumnya</h5><p class="small text-muted">Data ini tidak berubah ketika draft revisi diedit.</p><dl class="row mb-0"><dt class="col-sm-4">Site</dt><dd class="col-sm-8">{{ $previous['site_name'] ?? '-' }}</dd><dt class="col-sm-4">Penugasan</dt><dd class="col-sm-8">{{ $previous['assignment_position'] ?? '-' }} / {{ $previous['assignment_department'] ?? '-' }}</dd><dt class="col-sm-4">Area akses</dt><dd class="col-sm-8">{{ collect($application->submitted_snapshot['access_areas'] ?? [])->pluck('name')->join(', ') ?: '-' }}</dd><dt class="col-sm-4">Versi submission</dt><dd class="col-sm-8">{{ $application->submitted_snapshot['submission_version'] ?? '-' }}</dd></dl></div></div>
        @endif

        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <h5 class="fw-bold">Kategori SIMPER</h5>
            @forelse($categories as $category)
                <div class="border rounded p-3 mb-2"><strong>{{ $category['name'] }} / Level {{ $category['level'] }}</strong>@if(!empty($category['restrictions']))<div>Batasan: {{ $category['restrictions'] }}</div>@endif @if(!empty($category['supervisor_name']))<div>Supervisor: {{ $category['supervisor_name'] }}</div>@endif @if(!empty($category['activity_start_date']) || !empty($category['activity_end_date']))<div>Periode kegiatan/pelatihan: {{ $category['activity_start_date'] ?? '-' }} s.d. {{ $category['activity_end_date'] ?? '-' }}</div>@endif</div>
            @empty<p class="text-muted mb-0">Tidak ada kategori SIMPER.</p>@endforelse
        </div></div>

        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <h5 class="fw-bold">Dokumen snapshot submission {{ $application->submission_version }}</h5><p class="small text-muted">File ini merupakan referensi dokumen ketika pengajuan dikirim.</p>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Dokumen</th><th>Nomor/versi</th><th>Tanggal terbit</th><th class="text-end">File</th></tr></thead><tbody>
            @forelse($documents as $document)
                <tr><td><strong>{{ App\Models\ManpowerDocument::TYPES[$document->type] ?? $document->type }}</strong><div class="small">@if($hseApprovedCurrentSubmission)<span class="badge bg-success">Divalidasi dalam review HSE</span>@elseif(($document->metadata['verification_status'] ?? 'pending') === 'rejected')<span class="badge bg-danger">Perlu diganti</span>@else<span class="badge bg-warning text-dark">Menunggu review HSE</span>@endif</div></td><td>{{ $document->document_number ?: '-' }}<div class="small text-muted">versi sumber {{ $document->source_version }}</div></td><td>{{ $document->issued_at?->format('d-m-Y') ?? '-' }}</td><td class="text-end"><a target="_blank" class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.permit-applications.documents.show', [$application, $document]) }}">Preview</a></td></tr>
            @empty<tr><td colspan="4" class="text-center text-muted py-3">Belum ada snapshot dokumen.</td></tr>@endforelse
            </tbody></table></div>
            @if($application->documentSnapshots->count() > $documents->count())<div class="small text-muted mt-3">Tersimpan {{ $application->documentSnapshots->count() }} record dari seluruh versi submission.</div>@endif
        </div></div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <h5 class="fw-bold">Tindakan</h5>
            @can('update', $application)<form method="POST" action="{{ route('dashboard.permit-applications.submit', $application) }}" class="mb-3">@csrf<button class="btn btn-primary w-100">Kirim Pengajuan</button></form><form method="POST" action="{{ route('dashboard.permit-applications.cancel', $application) }}">@csrf<div class="input-group"><input name="notes" required class="form-control" placeholder="Alasan pembatalan"><button class="btn btn-outline-danger">Batalkan</button></div></form>@endcan
            @can('reviewHse', $application)
                <hr><h6 class="fw-bold">Review HSE</h6>
                <div class="alert alert-info small">Periksa data pengajuan dan seluruh berkas snapshot. Persetujuan HSE berarti data dan berkas pada submission ini telah divalidasi.</div>
                @if($application->created_by === auth()->id())<div class="alert alert-secondary small">Ini adalah pengajuan internal Anda. Self-review diizinkan dan tetap dicatat sebagai keputusan terpisah.</div>@endif
                <form method="POST" action="{{ route('dashboard.permit-applications.hse-approve', $application) }}" class="mb-3">@csrf<input type="hidden" name="version" value="{{ $application->version }}"><textarea name="notes" class="form-control mb-2" rows="2" placeholder="Catatan persetujuan (opsional)"></textarea><button class="btn btn-success w-100">Validasi Berkas &amp; Data — Setujui HSE</button></form>
                <form method="POST" action="{{ route('dashboard.permit-applications.hse-reject', $application) }}">@csrf<input type="hidden" name="version" value="{{ $application->version }}"><textarea name="notes" required class="form-control mb-2" rows="3" placeholder="Alasan pengembalian wajib"></textarea><button class="btn btn-outline-danger w-100">Kembalikan untuk Revisi</button></form>
            @endcan
            @can('submitToKtt', $application)
                <hr><h6 class="fw-bold">Validasi hasil ujian</h6>
                <form method="POST" action="{{ route('dashboard.permit-applications.submit-ktt', $application) }}">@csrf<textarea name="notes" class="form-control mb-2" rows="2" placeholder="Catatan validasi hasil (opsional)"></textarea><button class="btn btn-success w-100">Kirim ke Antrean KTT</button></form>
            @endcan
            @if($application->status === App\Enums\PermitApplicationStatus::KttReview)
            @can('reviewKtt', $application)
                <hr><h6 class="fw-bold">Keputusan KTT</h6>
                <p class="small text-muted">Persetujuan akan membekukan identitas reviewer, membuat klaim QR terenkripsi, dan meminta proses penerbitan permit.</p>
                <form method="POST" action="{{ route('dashboard.permit-applications.ktt-approve', $application) }}" class="mb-3">@csrf<input type="hidden" name="version" value="{{ $application->version }}"><textarea name="notes" class="form-control mb-2" rows="2" placeholder="Catatan persetujuan (opsional)"></textarea><button class="btn btn-success w-100">Setujui sebagai KTT</button></form>
                <form method="POST" action="{{ route('dashboard.permit-applications.ktt-reject', $application) }}">@csrf<input type="hidden" name="version" value="{{ $application->version }}"><textarea name="notes" required class="form-control mb-2" rows="3" placeholder="Alasan pengembalian wajib"></textarea><button class="btn btn-outline-danger w-100">Kembalikan untuk Revisi</button></form>
            @endcan
            @endif
        </div></div>

        @if($visibleExamToken)
            <div class="alert alert-success">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <strong>Token ujian: <span class="font-monospace fs-5">{{ $visibleExamToken->display_token }}</span></strong>
                        <div class="small">Masukkan bersama NIK peserta di <a href="{{ route('exam.login') }}" target="_blank" rel="noopener">halaman ujian</a>. Berlaku sampai {{ $visibleExamToken->expired_at->format('d-m-Y H:i') }}.</div>
                        @if(now()->lt($application->examSession->scheduled_start_at))
                            <div class="small text-warning-emphasis">Token dapat digunakan mulai {{ $application->examSession->scheduled_start_at->format('d-m-Y H:i') }}.</div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="navigator.clipboard.writeText(@js($visibleExamToken->display_token))">Salin token</button>
                </div>
            </div>
        @endif

        @if($application->status === App\Enums\PermitApplicationStatus::ExamRetryRequired && $application->examSession)
            @can('scheduleRetry', $application->examSession)
                <div class="card border-warning shadow-sm mb-4"><div class="card-body p-4">
                    <h5 class="fw-bold">Jadwalkan ujian ulang</h5>
                    <p class="small text-muted">Attempt sebelumnya tetap tersimpan. Alasan penjadwalan ulang akan dicatat pada timeline pengajuan.</p>
                    <form method="POST" action="{{ route('dashboard.exam-sessions.retry', $application->examSession) }}">
                        @csrf
                        <div class="row g-2 mb-2"><div class="col-md-6"><label class="form-label">Mulai</label><input required type="datetime-local" name="scheduled_start_at" class="form-control" value="{{ old('scheduled_start_at', now()->addHour()->format('Y-m-d\TH:i')) }}"></div><div class="col-md-6"><label class="form-label">Selesai</label><input required type="datetime-local" name="scheduled_end_at" class="form-control" value="{{ old('scheduled_end_at', now()->addHours(3)->format('Y-m-d\TH:i')) }}"></div></div>
                        <textarea required name="reason" class="form-control mb-2" rows="3" placeholder="Alasan dan catatan pembinaan sebelum ujian ulang">{{ old('reason') }}</textarea>
                        <button class="btn btn-warning w-100">Jadwalkan Attempt Berikutnya</button>
                    </form>
                </div></div>
            @endcan
        @endif

        @if($application->status !== App\Enums\PermitApplicationStatus::ExamRetryRequired)
        @can('configureExam', $application)
            @php($examSession = $application->examSession)
            <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                <h5 class="fw-bold">{{ $examSession ? 'Konfigurasi sesi ujian' : 'Buat sesi ujian' }}</h5>
                <p class="small text-muted">Aturan versi 1: durasi 60 menit, passing grade 80%, maksimal dua attempt.</p>
                <form method="POST" action="{{ $examSession ? route('dashboard.exam-sessions.update', $examSession) : route('dashboard.exam-sessions.store') }}">
                    @csrf
                    @if($examSession) @method('PUT') @endif
                    <input type="hidden" name="permit_application_id" value="{{ $application->id }}">
                    <div class="mb-2"><label class="form-label">Nama sesi</label><input required class="form-control" name="name" value="{{ old('name', $examSession?->name ?? 'Ujian '.$application->application_number) }}"></div>
                    <div class="mb-2"><label class="form-label">Deskripsi</label><textarea class="form-control" name="description" rows="2">{{ old('description', $examSession?->description) }}</textarea></div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><label class="form-label">Mulai</label><input required type="datetime-local" class="form-control" name="scheduled_start_at" value="{{ old('scheduled_start_at', $examSession?->scheduled_start_at?->format('Y-m-d\TH:i') ?? now()->subMinute()->format('Y-m-d\TH:i')) }}"></div>
                        <div class="col-md-6"><label class="form-label">Selesai</label><input required type="datetime-local" class="form-control" name="scheduled_end_at" value="{{ old('scheduled_end_at', $examSession?->scheduled_end_at?->format('Y-m-d\TH:i') ?? now()->addHours(2)->format('Y-m-d\TH:i')) }}"></div>
                    </div>
                    <input type="hidden" name="duration" value="60"><input type="hidden" name="passing_score" value="80"><input type="hidden" name="max_attempts" value="2">
                    <h6 class="fw-bold">Blueprint kategori soal</h6>
                    @foreach(collect($application->submitted_snapshot['categories'] ?? []) as $index => $simperCategory)
                        @php($mapping = $examSession?->blueprints->firstWhere('simper_category_id', $simperCategory['id']))
                        <div class="border rounded p-2 mb-2">
                            <strong>{{ $simperCategory['name'] }} / Level {{ $simperCategory['level'] }}</strong>
                            <input type="hidden" name="blueprints[{{ $index }}][simper_category_id]" value="{{ $simperCategory['id'] }}">
                            <div class="row g-2 mt-1">
                                <div class="col-8"><select required class="form-select" name="blueprints[{{ $index }}][question_category_id]"><option value="">Pilih kategori soal</option>@foreach($questionCategories as $questionCategory)<option value="{{ $questionCategory->id }}" @selected(old("blueprints.$index.question_category_id", $mapping?->question_category_id) == $questionCategory->id)>{{ $questionCategory->name }}</option>@endforeach</select></div>
                                <div class="col-4"><input required min="1" max="500" type="number" class="form-control" name="blueprints[{{ $index }}][question_count]" value="{{ old("blueprints.$index.question_count", $mapping?->question_count ?? 10) }}" title="Jumlah soal"></div>
                            </div>
                        </div>
                    @endforeach
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-outline-primary">{{ $examSession ? 'Perbarui draft' : 'Simpan draft' }}</button>
                        @if(!$examSession)<button name="activate" value="1" class="btn btn-success">Simpan dan Aktifkan</button>@endif
                    </div>
                </form>
                @if($examSession && !$examSession->is_active)
                    <form method="POST" action="{{ route('dashboard.exam-sessions.activate', $examSession) }}" class="mt-2">@csrf<button class="btn btn-success w-100">Aktifkan Sesi</button></form>
                @endif
            </div></div>
        @endcan
        @endif

        @if($application->type->requiresExam() && $application->examAttempts->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                <h5 class="fw-bold">Ringkasan seluruh attempt ujian</h5>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Attempt</th><th>Hasil</th><th>Nilai</th><th>Ambang</th><th>Finalisasi</th><th></th></tr></thead><tbody>
                @foreach($application->examAttempts->sortBy('attempt_number') as $attempt)
                    <tr><td>#{{ $attempt->attempt_number }}</td><td><span class="badge {{ $attempt->is_passed ? 'bg-success' : 'bg-danger' }}">{{ $attempt->is_passed ? 'Lulus' : 'Tidak lulus' }}</span></td><td>{{ number_format((float) $attempt->score, 2) }}%</td><td>{{ $attempt->passing_score_snapshot }}%</td><td>{{ $attempt->finalized_at?->format('d-m-Y H:i') ?? '-' }}</td><td class="text-end"><a href="{{ route('dashboard.exam-results.show', $attempt) }}" class="btn btn-sm btn-outline-primary">Detail</a></td></tr>
                @endforeach
                </tbody></table></div>
            </div></div>
        @endif

        @if($application->examSession)
            @php($examSession = $application->examSession)
            <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                <h5 class="fw-bold">Sesi ujian</h5>
                <dl class="row small mb-2"><dt class="col-5">Status</dt><dd class="col-7">{{ $examSession->status }}</dd><dt class="col-5">Jadwal</dt><dd class="col-7">{{ $examSession->scheduled_start_at?->format('d-m-Y H:i') }} s.d. {{ $examSession->scheduled_end_at?->format('d-m-Y H:i') }}</dd><dt class="col-5">Aturan</dt><dd class="col-7">{{ $examSession->duration }} menit / {{ $examSession->passing_score }}% / {{ $examSession->max_attempts }} attempt</dd></dl>
                @foreach($examSession->blueprints as $blueprint)<div class="small">{{ $blueprint->simperCategory->name }} → {{ $blueprint->questionCategory->name }} ({{ $blueprint->question_count }} soal)</div>@endforeach
                @can('issueToken', $examSession)
                    <div class="small text-muted mt-2">Pembuatan token akan membuka akses ujian saat ini apabila waktu mulai masih di masa depan.</div>
                    <form method="POST" action="{{ route('dashboard.exam-sessions.generate-token', $examSession) }}" class="mt-3">@csrf<button class="btn btn-primary w-100">Buat / Rotasi Token</button></form>
                @endcan
                @php($latestToken = $examSession->tokens->sortByDesc('created_at')->first())
                @if($latestToken)<div class="small text-muted mt-2">Token terakhir dibuat {{ $latestToken->created_at->format('d-m-Y H:i') }}, berlaku sampai {{ $latestToken->expired_at->format('d-m-Y H:i') }}{{ $latestToken->revoked_at ? ' (dinonaktifkan)' : '' }}.</div>@endif
            </div></div>
        @endif

        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><h5 class="fw-bold">Riwayat review</h5>@forelse($application->reviews->sortByDesc('reviewed_at') as $review)<div class="border rounded p-3 mb-2"><strong>{{ strtoupper($review->stage) }} / {{ $review->decision === 'approved' ? 'Disetujui' : 'Dikembalikan' }}</strong><div class="small text-muted">{{ $review->reviewer_snapshot['name'] ?? $review->reviewer?->name ?? 'Pengguna dihapus' }} / {{ $review->reviewed_at->format('d-m-Y H:i') }} / submission {{ $review->submission_version }}</div>@if($review->stage === 'ktt' && $review->decision === 'approved')<div class="small text-success mt-1">Klaim QR terenkripsi tersimpan dan permintaan penerbitan telah dicatat.</div>@endif @if($review->notes)<div class="mt-2">{{ $review->notes }}</div>@endif</div>@empty<p class="text-muted mb-0">Belum ada keputusan review.</p>@endforelse</div></div>

        <div class="card border-0 shadow-sm"><div class="card-body p-4"><h5 class="fw-bold">Timeline</h5>@forelse($application->statusHistories->sortByDesc('transitioned_at') as $history)<div class="border-start border-primary ps-3 pb-3"><strong>{{ ucwords(str_replace('_', ' ', $history->action)) }}</strong><div class="small">{{ $history->from_status->value }} &rarr; {{ $history->to_status->value }}</div><div class="small text-muted">{{ $history->actor?->name ?? 'Sistem' }} / {{ $history->transitioned_at->format('d-m-Y H:i') }} / versi {{ $history->application_version }}</div>@if($history->notes)<div class="mt-1">{{ $history->notes }}</div>@endif</div>@empty<p class="text-muted">Belum ada transisi. Pengajuan masih draft.</p>@endforelse</div></div>
    </div>
</div>
@endsection
