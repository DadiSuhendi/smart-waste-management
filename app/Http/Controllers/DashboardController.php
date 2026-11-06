<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\WasteCategory;
use App\Models\StatusHistory;
use App\Models\WasteReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $request->validate(['days' => ['nullable', 'integer', 'in:7,30,90']]);
        $days = (int) $request->input('days', 30);
        $query = WasteReport::visibleTo($request->user());
        $counts = (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $stats = ['total' => (int) $counts->sum()];
        foreach (ReportStatus::cases() as $status) {
            $stats[$status->value] = (int) ($counts[$status->value] ?? 0);
        }
        $stats['completion_rate'] = $stats['total'] ? round($stats['completed'] / $stats['total'] * 100) : 0;
        $period = (clone $query)->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())->get(['created_at', 'category']);
        $categories = collect(WasteCategory::cases())->map(fn ($category) => ['key' => $category->value, 'label' => $category->label(), 'total' => $period->where('category', $category)->count()]);
        $daily = $period->groupBy(fn ($item) => $item->created_at->format('Y-m-d'));
        $trend = collect(range($days - 1, 0))->map(function ($offset) use ($daily): array {
            $date = now()->subDays($offset);

            return ['date' => $date->format('Y-m-d'), 'label' => $date->format('d M'), 'total' => $daily->get($date->format('Y-m-d'), collect())->count()];
        });
        if ($request->is('api/*')) {
            return response()->json(['data' => compact('stats', 'categories', 'trend', 'days')]);
        }
        $recent = (clone $query)->with('user')->latest('id')->limit(5)->get();
        $activity = StatusHistory::whereIn('waste_report_id', (clone $query)->select('id'))->with('user')->latest('id')->limit(4)->get();

        return view('dashboard', compact('stats', 'categories', 'trend', 'recent', 'activity', 'days'));
    }
}
