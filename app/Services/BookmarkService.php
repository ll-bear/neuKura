<?php

namespace App\Services;

use App\Jobs\ProcessBookmarkJob;
use App\Models\Bookmark;
use App\Repositories\Interfaces\BookmarkRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BookmarkService
{
    public function __construct(
        private BookmarkRepositoryInterface $bookmarkRepository,
        private ScraperService $scraperService,
        private OllamaService $ollamaService,
    ) {}

    public function index(int $userId): LengthAwarePaginator
    {
        return $this->bookmarkRepository->paginateByUser($userId);
    }

    public function store(int $userId, string $url, ?string $memo): Bookmark
    {
        // 1. スクレイピング
        $scraped = $this->scraperService->scrape($url);

        // 2. 保存（要約・カテゴリ・ベクトルはキューで非同期処理）
        $bookmark = $this->bookmarkRepository->create([
            'user_id' => $userId,
            'category_id' => null,
            'url' => $url,
            'title' => $scraped['title'],
            'memo' => $memo,
            'summary' => null,
            'vector' => null,
        ]);

        ProcessBookmarkJob::dispatch($bookmark->id, $userId, $scraped['text']);

        return $bookmark;
    }

    public function search(int $userId, string $query): Collection
    {
        $queryVector = $this->ollamaService->embed($query);

        $bookmarks = $this->bookmarkRepository->findWithVectorsByUser($userId);

        return $bookmarks
            ->map(function ($bookmark) use ($queryVector) {
                $bookmark->similarity = $this->cosineSimilarity(
                    $queryVector,
                    $bookmark->vector
                );

                return $bookmark;
            })
            ->sortByDesc('similarity')
            ->take(10)
            ->values();
    }

    public function destroy(int $userId, Bookmark $bookmark): void
    {
        abort_if($bookmark->user_id !== $userId, 403);
        $this->bookmarkRepository->delete($bookmark);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }
        $dot = array_sum(array_map(fn ($x, $y) => $x * $y, $a, $b));
        $normA = sqrt(array_sum(array_map(fn ($x) => $x ** 2, $a)));
        $normB = sqrt(array_sum(array_map(fn ($x) => $x ** 2, $b)));

        return ($normA && $normB) ? $dot / ($normA * $normB) : 0.0;
    }
}
