<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CategoryRequest;
use App\Services\CategoryService;


class CategoryController extends Controller
{
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    
    private CategoryService $categoryService;
    
    public function index()
    {
        return view('config.category.index', ['categories' => $this->categoryService->getCategories()]);
    }

    public function store(CategoryRequest $request)
    {
        $this->categoryService->store($request->all());

        return response()->json($category, 201);

        //return redirect()->route('config.category.index')->with('success', 'カテゴリを追加しました');
    }

    public function update($id, CategoryRequest $request)
    {
        $this->categoryService->update($id, $request->all());

        return response()->json($category);
        
        //return redirect()->route('config.category.index')->with('success', 'カテゴリを更新しました');
    }

    public function destroy($id)
    {
        $this->categoryService->destroy($id);

        return response()->json(null, 204);

        //return redirect()->route('config.category.index')->with('success', 'カテゴリを削除しました');
    }

    public function sort(Request $request)
    {
        $this->categoryService->sort($request->all());

        return redirect()->route('config.category.index')->with('success', 'カテゴリを更新しました');
    }
}
