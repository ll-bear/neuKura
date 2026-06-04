<?php

namespace Tests\Unit;

use App\Jobs\ProcessBookmarkJob;
use App\Models\Bookmark;
use App\Repositories\Interfaces\BookmarkRepositoryInterface;
use App\Services\BookmarkService;
use App\Services\OllamaService;
use App\Services\ScraperService;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class BookmarkServiceStoreTest extends TestCase
{
    public function test_store_dispatches_process_job_without_synchronous_ai(): void
    {
        Queue::fake();

        $bookmark = new Bookmark([
            'id' => 42,
            'user_id' => 1,
            'url' => 'https://example.com',
        ]);
        $bookmark->id = 42;

        $scraper = Mockery::mock(ScraperService::class);
        $scraper->shouldReceive('scrape')
            ->once()
            ->with('https://example.com')
            ->andReturn(['title' => 'Example', 'text' => 'page body', 'imageUrl' => 'https://example.com/og.jpg']);

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldNotReceive('summarizeAndCategorize');
        $ollama->shouldNotReceive('embed');

        $repository = Mockery::mock(BookmarkRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data): bool {
                return $data['vector'] === null
                    && $data['summary'] === null
                    && $data['category_id'] === null
                    && $data['image_url'] === 'https://example.com/og.jpg';
            }))
            ->andReturn($bookmark);

        $service = new BookmarkService($repository, $scraper, $ollama);

        $result = $service->store(1, 'https://example.com', 'memo');

        $this->assertSame(42, $result->id);

        Queue::assertPushed(ProcessBookmarkJob::class, function (ProcessBookmarkJob $job): bool {
            return $job->bookmarkId === 42
                && $job->userId === 1
                && $job->text === 'page body';
        });
    }
}
