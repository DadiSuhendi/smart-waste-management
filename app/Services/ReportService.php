<?php

namespace App\Services;

use App\Http\Requests\ReportRequest;
use App\Models\WasteReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportService
{
    public function save(ReportRequest $request, ?WasteReport $report = null): WasteReport
    {
        $data = $request->safe()->except('photo');
        $photo = $request->file('photo');
        $oldPhoto = $report?->photo_path;
        $newPhoto = $photo?->store('reports', 'local');
        $prediction = $photo ? app(ImageClassifier::class)->predict($photo) : null;
        try {
            $report = DB::transaction(function () use ($request, $report, $data, $newPhoto, $prediction, &$oldPhoto): WasteReport {
                $isNew = $report === null;
                if (! $isNew) {
                    $report = WasteReport::lockForUpdate()->findOrFail($report->id);
                    Gate::authorize('update', $report);
                    $oldPhoto = $report->photo_path;
                }
                $report ??= new WasteReport;
                $report->fill($data);
                if ($isNew) {
                    $report->user_id = $request->user()->id;
                }
                if ($newPhoto) {
                    $report->photo_path = $newPhoto;
                    $report->ai_category = $prediction['category'] ?? null;
                    $report->ai_confidence = $prediction['confidence'] ?? null;
                }
                $report->save();
                if ($isNew) {
                    $report->histories()->create(['user_id' => $request->user()->id, 'status' => 'pending', 'note' => 'Laporan diterima dan menunggu peninjauan.']);
                }

                return $report->refresh();
            });
        } catch (Throwable $exception) {
            if ($newPhoto) {
                Storage::disk('local')->delete($newPhoto);
            }
            throw $exception;
        }
        if ($newPhoto && $oldPhoto) {
            Storage::disk('local')->delete($oldPhoto);
        }

        return $report;
    }
}
