<?php

namespace App\Http\Controllers\API;

use App\Models\Bookmark;
use App\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController
{
    public function __construct(
        private BookmarkService $bookmarkService,
    ) {
        $this->bookmarkService = $bookmarkService;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url'  => 'required|url|max:2048',
            'memo' => 'nullable|string|max:500',
        ]);

        $bookmark = $this->bookmarkService->store(
            auth()->id(),
            $validated['url'],
            $validated['memo'] ?? null,
        );

        return response()->json($bookmark->load('category'), 201);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|max:200']);

        $results = $this->bookmarkService->search(auth()->id(), $request->q);
        return response()->json($results);
    }

    public function destroy(Bookmark $bookmark): JsonResponse
    {
        $this->bookmarkService->destroy(auth()->id(), $bookmark);
        return response()->json(null, 204);
    }
}
