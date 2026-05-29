<?php

namespace App\Repositories;

use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    protected function model()
    {
        return Category::class;
    }

    public function getCategories(int $userId)
    {
        return $this->model
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                ->orWhereNull('user_id');
            })
            ->orderBy('order')
            ->get();
    }

    public function store(array $data)
    {
        return $this->model->create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'order' => $data['order'] ?? 0,
        ]);
    }

    public function update($id, array $data)
    {
        return $this->model->where('id', $id)
            ->update([
                'name' => $data['name'],
                'order' => $data['order'] ?? 0,
            ])->where('id', $id)->first();

    }

    public function destroy($id)
    {
        // ユーザーIDが登録されているもののみ削除可能という仕様
        if ($this->find($id)->user_id != auth()->id()) {
            return back()->withErrors('デフォルトカテゴリは削除できません。');
        }

        $this->model->where('id', $id)->delete();

        return true;
    }

    public function sort(array $data)
    {
        $this->model->where('id', $data['id'])->update(['order' => $data['order'], 'name' => $data['name']]);

        return true;
    }

    public function getByUser(int $userId)
    {
        return $this->model::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id');
        })->get(['id', 'name']);
    }
}