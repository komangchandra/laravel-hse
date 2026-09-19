<?php

namespace App\Http\Controllers;

use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index()
    {
        // 1. Logins per day (last 30 days) - Optimized for Chart.js
        $loginsPerDay = UserLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total_logins')
        )
            ->where('action', 'login')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 2. Active users last 7 days
        $activeUsers7 = UserLog::where('action', 'login')
            ->where('created_at', '>=', now()->subDays(7))
            ->distinct('user_id')
            ->count('user_id');

        // 3. Active users last 30 days
        $activeUsers30 = UserLog::where('action', 'login')
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct('user_id')
            ->count('user_id');

        // 4. Most used apps
        // $appUsage = UserLog::select('app', DB::raw('count(*) as usage_count'))
        //     ->groupBy('app')
        //     ->orderByDesc('usage_count')
        //     ->get();

        // Aggregate cast JSON in PHP so this query works consistently on MySQL, SQLite, and PostgreSQL.
        $metaLogs = UserLog::query()->whereNotNull('meta')->get(['meta']);
        $deviceStats = $metaLogs->groupBy(fn (UserLog $log) => data_get($log->meta, 'device', 'Unknown'))
            ->map(fn ($logs, $device) => (object) ['device' => $device, 'count' => $logs->count()])->values();
        $ipStats = $metaLogs->groupBy(fn (UserLog $log) => data_get($log->meta, 'ip', 'Unknown'))
            ->map(fn ($logs, $ip) => (object) ['ip' => $ip, 'count' => $logs->count()])
            ->sortByDesc('count')->take(10)->values();

        // 7. Recent logs
        $recentLogs = UserLog::latest()->take(10)->get();

        return view('analytics.index', compact(
            'loginsPerDay',
            'activeUsers7',
            'activeUsers30',
            'deviceStats',
            'ipStats',
            'recentLogs'
        ));
    }

    /**
     * Export User Logs to CSV
     */
    public function export(): StreamedResponse
    {
        $fileName = 'anveshika_user_logs_'.date('Y-m-d_H-i').'.csv';

        // We use chunking if the table gets massive to avoid memory crashes
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, ['ID', 'User ID', 'Name', 'Email', 'Mobile', 'Action', 'App', 'Created At']);

            // Use cursor to handle large datasets efficiently
            UserLog::latest()->cursor()->each(function ($log) use ($file) {
                fputcsv($file, [
                    $log->id,
                    $log->user_id,
                    $log->name,
                    $log->email,
                    $log->mobile,
                    $log->action,
                    $log->app,
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            });

            fclose($file);
        }, 200, $headers);
    }

    public function userLogs(Request $request)
    {
        $query = UserLog::where('user_id', Auth::id());

        // Optional: Filter by action if provided
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return view('analytics.userlog', compact('logs'));
    }
}
