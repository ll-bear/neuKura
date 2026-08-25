<?php

namespace App\Repositories;

use App\Models\Bookmark;
use App\Repositories\Interfaces\BookmarkRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BookmarkRepository extends BaseRepository implements BookmarkRepositoryInterface
{
    protected function model()
    {
        return Bookmark::class;
    }

    public function paginateByUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model::with('category')
            ->where('user_id', $userId)
            // ログイン情報保存だけのために自動作成されたブックマーク
            // (summary/memo/vectorが全て空)を一覧から除外する。
            // 通常の保存(共有シート/手動保存)は必ずmemoかsummaryのどちらかが
            // 入る想定のため、これらが全て空のものだけが対象になる。
            ->where(function ($q) {
                $q->whereNotNull('summary')
                    ->orWhereNotNull('memo')
                    ->orWhereNotNull('vector');
            })
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

    /**
     * ローカルLLM(埋め込み生成)が使えない場合のフォールバック用、
     * title/summary/memo/urlに対する単純なワード検索。
     */
    public function searchByKeyword(int $userId, string $query, int $limit = 10): Collection
    {
        return $this->model::with('category')
            ->where('user_id', $userId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('summary', 'like', "%{$query}%")
                    ->orWhere('memo', 'like', "%{$query}%")
                    ->orWhere('url', 'like', "%{$query}%");
            })
            ->latest()
            ->take($limit)
            ->get();
    }

    public function create(array $data)
    {
        return $this->model::create($data);
    }

    public function delete($id)
    {
        Bookmark::findOrFail($id)->delete();
    }
}
