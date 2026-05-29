<?php

namespace App\Services;

use App\Repositories\Interfaces\CategoryRepositoryInterface;

class CategoryService
{
  public function __construct(
    private CategoryRepositoryInterface $categoryRepository,
  ){
    $this->categoryRepository = $categoryRepository;
  }

  public function getCategories()
  {
    $userId = auth()->id();
    
    return $this->categoryRepository->getCategories($userId);
  }

  public function store(array $data)
  {
    return $this->categoryRepository->store($data);
  }

  public function update($id, array $data)
  {
    // 他ユーザーのカテゴリ、またはデフォルト(user_id=null)は更新不可
    if ($this->categoryRepository->show($id)->user_id != auth()->id()) {
        abort(403);
    }

    return $this->categoryRepository->update($id, $data);
  }

  public function destroy($id)
  {
    return $this->categoryRepository->destroy($id);
  }

  public function sort(array $data)
  {
    $order = 0;
    foreach($data['name'] as $id => $name){
      $arr = [
          "id" => (int)$id,
          "name" => $name,
      ];

      $arr['order'] = ++ $order;

      $this->categoryRepository->sort($arr);
    }
  }
}