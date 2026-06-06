<?php

namespace App\Repositories;

use App\Models\Bookmark;
use App\Repositories\Interfaces\BookmarkRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BookmarkRepository extends BaseRepository  implements BookmarkRepositoryInterface
{
    protected function model()
    {
        return Bookmark::class;
    }

    public function paginateByUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model::with('category')
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function findWithVectorsByUser(int $userId): Collection
    {
        return $this->model::with('category')
            ->where('user_id', $userId)
            ->whereNotNull('vector')
            ->get();
    }

    public function create(array $data)
    {
        return $this->model::create($data);
    }

    public function delete(int $id)
    {
        Bookmark::findOrFail($id)->delete();
    }
}
