<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Category;
use App\Services\BookmarkService;
use App\Services\CategoryService;

class BookmarkWebController extends Controller
{
    public function __construct(
        private BookmarkService $bookmarkService,
        private CategoryService $categoryService,
    ) {
        $this->bookmarkService = $bookmarkService;
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        $bookmarks = $this->bookmarkService->index(auth()->id());
        $categories = $this->categoryService->getCategories();

        return view('bookmarks.index', compact('bookmarks', 'categories'));
    }

    /**
     * 「もっと見る」用のJSON API。クエリの ?page=N をLaravelのpaginateが
     * 自動的に読み取るため、ここではページ番号を明示的に渡す必要はない。
     */
    public function paginate()
    {
        $bookmarks = $this->bookmarkService->index(auth()->id());

        return response()->json([
            'data' => $bookmarks->items(), // categoryは既にeager loaded済み
            'current_page' => $bookmarks->currentPage(),
            'last_page' => $bookmarks->lastPage(),
            'total' => $bookmarks->total(),
        ]);
    }
}
