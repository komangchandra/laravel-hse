<?php

namespace App\Http\Controllers;

use App\Models\PermitIssuance;
use App\Services\PermitIssuanceService;
use App\Services\OperationalAuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PermitCardController extends Controller
{
    public function __construct(
        private readonly PermitIssuanceService $issuances,
        private readonly OperationalAuditService $audit,
    ) {}

    public function show(PermitIssuance $issuance): View
    {
        $this->authorize('view', $issuance);
        $issuance->load(['application', 'printLogs.printer']);

        return view('dashboard.permit-cards.show', compact('issuance'));
    }

    public function pdf(Request $request, PermitIssuance $issuance)
    {
        $this->authorize('download', $issuance);
        abort_unless($issuance->isPrintable(), 409, 'Kartu hanya dapat dicetak ketika permit aktif dan belum kedaluwarsa.');
        $snapshot = $issuance->print_snapshot;
        $qrSvg = base64_encode((string) QrCode::format('svg')->size(220)->margin(0)
            ->generate($snapshot['verification_url']));
        $ownerLogoDataUri = $this->pngDataUri(base_path('codex/img/owner-gpu.png'));
        $safetyLogoDataUri = $this->pngDataUri(base_path('codex/img/logo-safety-first.png'));

        $action = $issuance->printLogs()->exists() ? 'reprint' : 'initial_print';
        $issuance->printLogs()->create([
            'printed_by' => $request->user()->id,
            'action' => $action,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ],
            'printed_at' => now(),
        ]);
        $this->audit->record(
            'permit_card.'.$action,
            $issuance,
            $request->user(),
            $issuance->owner_id,
            $issuance->status,
            $issuance->status,
            ['mine_permit_number' => $issuance->mine_permit_number],
        );

        return Pdf::loadView('dashboard.permit-cards.pdf', compact(
            'issuance', 'snapshot', 'qrSvg', 'ownerLogoDataUri', 'safetyLogoDataUri'
        ))
            ->setPaper([0, 0, 283.46457, 425.19685])
            ->stream('permit-'.$issuance->mine_permit_number.'.pdf');
    }

    public function revoke(Request $request, PermitIssuance $issuance): RedirectResponse
    {
        $this->authorize('revoke', $issuance);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            $this->issuances->revoke($issuance, $request->user(), $validated['reason']);
        } catch (DomainException $exception) {
            return back()->withErrors(['permit' => $exception->getMessage()]);
        }

        return redirect()->route('dashboard.permit-cards.show', $issuance)
            ->with('success', 'Permit berhasil dicabut. QR publik kini menampilkan status revoked.');
    }

    private function pngDataUri(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
