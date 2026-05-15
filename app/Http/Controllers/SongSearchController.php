<?php

namespace App\Http\Controllers;

use App\Services\KaraokeSongCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SongSearchController extends Controller
{
    public function __invoke(Request $request, KaraokeSongCatalog $songCatalog): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        try {
            $songs = $songCatalog->searchAndCache($validated['q']);

            return response()->json([
                'results' => $songs->map->toVideoArray()->all(),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'results' => [],
            ], 503);
        }
    }
}
