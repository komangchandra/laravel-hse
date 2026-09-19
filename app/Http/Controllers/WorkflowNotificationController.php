<?php

namespace App\Http\Controllers;

use App\Models\WorkflowNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = WorkflowNotification::query()->where('user_id', $request->user()->id)
            ->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, WorkflowNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return $notification->action_url ? redirect()->to($notification->action_url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        WorkflowNotification::query()->where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai telah dibaca.');
    }
}
