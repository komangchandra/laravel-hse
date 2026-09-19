<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('audit.view'), 403);
        $user = $request->user();
        $query = AuditLog::query()->with('organization')
            ->when(! $user->isDeveloper(), fn (Builder $query) => $query->where('organization_id', $user->ownerOrganizationId()))
            ->when($user->hasRole('safety_mitra'), fn (Builder $query) => $query->where('actor_id', $user->id))
            ->when($user->isDeveloper() && $request->filled('owner_id'), fn (Builder $query) => $query->where('organization_id', $request->integer('owner_id')))
            ->when($request->filled('action'), fn (Builder $query) => $query->where('action', 'like', '%'.trim((string) $request->input('action')).'%'))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('occurred_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('occurred_at', '<=', $request->input('date_to')));

        return view('audit.index', [
            'logs' => $query->latest('occurred_at')->paginate(25)->withQueryString(),
            'owners' => $user->isDeveloper() ? Partner::owners()->orderBy('legal_name')->get() : collect(),
        ]);
    }
}
