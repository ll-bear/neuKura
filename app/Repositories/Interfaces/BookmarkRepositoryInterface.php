<?php

namespace App\Repositories\Interfaces;

use App\Models\Bookmark;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BookmarkRepositoryInterface
{
    public function paginateByUser(int $userId, int $perPage = 20): LengthAwarePaginator;
    public function searchByKeyword(int $userId, string $query, int $limit = 10): Collection;
    public function findWithVectorsByUser(int $userId): Collection;
    public function create(array $data);
    public function delete($id);
}