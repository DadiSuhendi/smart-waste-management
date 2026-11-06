<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\WasteCategory;
use App\Http\Requests\ReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\WasteReport;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View|AnonymousResourceCollection
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', Rule::enum(ReportStatus::class)], 'category' => ['nullable', Rule::enum(WasteCategory::class)]]);
        $reports = WasteReport::visibleTo($request->user())->with('user')
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('address', 'like', '%'.$search.'%')))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->latest('id')->paginate(10)->withQueryString();

        return $request->is('api/*') ? ReportResource::collection($reports) : view('reports.index', compact('reports'));
    }

    public function create(): View
    {
        return view('reports.form', ['report' => new WasteReport]);
    }

    public function store(ReportRequest $request, ReportService $service): JsonResponse|RedirectResponse
    {
        $report = $service->save($request);

        return $request->is('api/*') ? (new ReportResource($report->load('user')))->response()->setStatusCode(201)
            : redirect()->route('reports.show', $report)->with('success', 'Laporan berhasil dikirim. Terima kasih sudah peduli lingkungan.');
    }

    public function show(Request $request, WasteReport $report): View|ReportResource
    {
        Gate::authorize('view', $report);
        $report->load(['user', 'histories.user']);

        return $request->is('api/*') ? new ReportResource($report) : view('reports.show', compact('report'));
    }

    public function edit(WasteReport $report): View
    {
        Gate::authorize('update', $report);

        return view('reports.form', compact('report'));
    }

    public function update(ReportRequest $request, WasteReport $report, ReportService $service): ReportResource|RedirectResponse
    {
        Gate::authorize('update', $report);
        $report = $service->save($request, $report);

        return $request->is('api/*') ? new ReportResource($report->load('user')) : redirect()->route('reports.show', $report)->with('success', 'Perubahan laporan disimpan.');
    }

    public function destroy(Request $request, WasteReport $report): JsonResponse|RedirectResponse
    {
        $path = DB::transaction(function () use ($report): string {
            $locked = WasteReport::lockForUpdate()->findOrFail($report->id);
            Gate::authorize('delete', $locked);
            $path = $locked->photo_path;
            $locked->delete();

            return $path;
        });
        Storage::disk('local')->delete($path);

        return $request->is('api/*') ? response()->json(['message' => 'Laporan dihapus.']) : redirect()->route('reports.index')->with('success', 'Laporan berhasil dihapus.');
    }

    public function status(Request $request, WasteReport $report): ReportResource|RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['status' => ['required', Rule::enum(ReportStatus::class)], 'note' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($report, $data, $request): void {
            $locked = WasteReport::lockForUpdate()->findOrFail($report->id);
            if ($locked->status->value !== $data['status']) {
                $locked->status = $data['status'];
                $locked->save();
                $locked->histories()->create(['user_id' => $request->user()->id, 'status' => $data['status'], 'note' => $data['note'] ?? null]);
            }
        });

        return $request->is('api/*') ? new ReportResource($report->refresh()->load(['user', 'histories.user'])) : back()->with('success', 'Status laporan diperbarui.');
    }

    public function photo(WasteReport $report): StreamedResponse
    {
        Gate::authorize('view', $report);
        abort_unless(Storage::disk('local')->exists($report->photo_path), 404);

        return Storage::disk('local')->response($report->photo_path, null, ['Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }
}
