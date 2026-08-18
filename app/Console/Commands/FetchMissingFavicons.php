<?php

namespace App\Console\Commands;

use App\Jobs\FetchFaviconJob;
use App\Models\Bookmark;
use Illuminate\Console\Command;

class FetchMissingFavicons extends Command
{
    /**
     * php artisan favicons:fetch        → favicon_path が未設定のものだけ対象
     * php artisan favicons:fetch --all  → 全bookmarkを対象に再取得
     */
    protected $signature = 'favicons:fetch {--all : 既に取得済みのものも含めて全件再取得する}';

    protected $description = '1PasswordインポートなどでURLはあるがfaviconが未取得のbookmarkに対してFetchFaviconJobを流す';

    public function handle(): int
    {
        $query = Bookmark::query()->whereNotNull('url');

        if (! $this->option('all')) {
            $query->whereNull('favicon_path');
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('対象のbookmarkはありませんでした。');

            return self::SUCCESS;
        }

        $this->info("{$count} 件のbookmarkに対してfavicon取得Jobをdispatchします...");

        $bar = $this->output->createProgressBar($count);

        $query->select('id')->chunkById(100, function ($bookmarks) use ($bar) {
            foreach ($bookmarks as $bookmark) {
                FetchFaviconJob::dispatch($bookmark->id);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('dispatch完了です。キューワーカーが起動していることを確認してください(php artisan queue:work)。');

        return self::SUCCESS;
    }
}
