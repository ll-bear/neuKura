<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
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

    public function update(Request $request, Bookmark $bookmark): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $bookmark = $this->bookmarkService->update(
            auth()->id(),
            $bookmark,
            $validated
        );

        return response()->json($bookmark->load('category'));
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|max:200']);

        $results = $this->bookmarkService->search(auth()->id(), $request->q);
        return response()->json($results);
    }

    public function destroy(Bookmark $bookmark): JsonResponse
    {
        \Log::debug('destroy called', [
            'bookmark_id' => $bookmark->id,
            'bookmark_user_id' => $bookmark->user_id,
            'auth_id' => auth()->id(),
        ]);
        
        $this->bookmarkService->destroy(auth()->id(), $bookmark);
        return response()->json(null, 204);
    }
}
