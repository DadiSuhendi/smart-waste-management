<?php

namespace App\Http\Controllers;

use App\Services\ImageClassifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    public function __invoke(Request $request, ImageClassifier $classifier): JsonResponse
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=8000,max_height=8000']]);
        $result = $classifier->predict($request->file('photo'));

        return $result ? response()->json(['data' => $result]) : response()->json(['message' => 'Deteksi belum tersedia. Anda tetap dapat memilih kategori dan mengirim laporan.'], 503);
    }
}
