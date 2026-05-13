<?php

namespace App\Http\Controllers;

use App\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SongSearchController extends Controller
{
    public function __invoke(Request $request, YouTubeService $youTubeService): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        try {
            return response()->json([
                'results' => $youTubeService->search($validated['q']),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'results' => [],
            ], 503);
        }
    }
}
