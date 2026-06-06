<?php

namespace App\Console\Commands;

use App\Models\Bookmark;
use App\Services\OllamaService;
use Illuminate\Console\Command;

class RevectorizeBookmarks extends Command
{
    protected $signature = 'bookmarks:revectorize
                            {--id= : 特定のブックマークIDのみ処理}
                            {--force : ベクトル済みのものも再処理する}';

    protected $description = '既存ブックマークのベクトルを再生成する';

    public function __construct(private OllamaService $ollamaService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $query = Bookmark::whereNotNull('summary');

        // 特定IDのみ処理
        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        // --force がない場合はベクトル未生成のものだけ処理
        if (!$this->option('force')) {
            $query->whereNull('vector');
        }

        $bookmarks = $query->get();

        if ($bookmarks->isEmpty()) {
            $this->info('対象のブックマークがありません。');
            $this->line('--force オプションを付けると全件再処理できます。');
            return;
        }

        $this->info("対象: {$bookmarks->count()} 件");

        $bar = $this->output->createProgressBar($bookmarks->count());
        $bar->start();

        $success = 0;
        $failed  = 0;

        foreach ($bookmarks as $bookmark) {
            try {
                $vector = $this->ollamaService->embed($bookmark->summary);

                if (empty($vector)) {
                    $this->newLine();
                    $this->warn("スキップ (ID: {$bookmark->id}) ベクトルが空でした");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $bookmark->update(['vector' => $vector]);
                $success++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("失敗 (ID: {$bookmark->id}): {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("完了 — 成功: {$success} 件 / 失敗: {$failed} 件");
    }
}
