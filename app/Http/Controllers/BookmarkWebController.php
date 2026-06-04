<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Category;
use App\Services\BookmarkService;
use App\Services\CategoryService;

class BookmarkWebController
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
}
