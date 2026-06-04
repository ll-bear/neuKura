<?php

namespace App\Jobs;

use App\Models\Bookmark;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Services\OllamaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class ProcessBookmarkJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(
        public int $bookmarkId,
        public int $userId,
        public string $text,
    ) {}

    public function handle(
        OllamaService $ollamaService,
        CategoryRepositoryInterface $categoryRepository,
    ): void {
        $bookmark = Bookmark::query()->find($this->bookmarkId);

        if ($bookmark === null) {
            return;
        }

        $categories = $categoryRepository->getByUser($this->userId)->toArray();

        $aiResult = $ollamaService->summarizeAndCategorize($this->text, $categories);

        $summaryText = $aiResult['summary'] ?? $this->text;

        $vector = $ollamaService->embed($summaryText);

        if ($vector === []) {
            throw new RuntimeException("Embedding failed for bookmark {$this->bookmarkId}");
        }

        $bookmark->update([
            'category_id' => $aiResult['category_id'] ?? null,
            'summary' => $aiResult['summary'] ?? null,
            'vector' => $vector,
        ]);
    }
}
