<?php

namespace App\Console\Commands;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOnePasswordCsv extends Command
{
    /**
     * php artisan credentials:import /path/to/export.csv --user=1
     */
    protected $signature = 'credentials:import
        {path : CSVファイルのパス}
        {--user= : 取り込み先のユーザーID}';

    protected $description = '1PasswordエクスポートCSVからBookmark/Credentialを作成する';

    public function handle(): int
    {
        $path = $this->argument('path');
        $userId = $this->option('user');

        if (! file_exists($path)) {
            $this->error("ファイルが見つかりません: {$path}");
            return self::FAILURE;
        }

        if (! $userId) {
            $this->error('--user=ID を指定してください(例: --user=1)');
            return self::FAILURE;
        }

        $user = User::find($userId);
        if (! $user) {
            $this->error("ユーザーID {$userId} が見つかりません");
            return self::FAILURE;
        }

        $rows = $this->parseCsv($path);
        if ($rows === null) {
            $this->error('CSVの解析に失敗しました(ヘッダー行に Title/Url/Username/Password が見つかりません)');
            return self::FAILURE;
        }

        $created = 0;
        $skippedNoUrl = 0;
        $bookmarksCreated = 0;
        $bookmarksReused = 0;

        DB::transaction(function () use ($rows, $user, &$created, &$skippedNoUrl, &$bookmarksCreated, &$bookmarksReused) {
            foreach ($rows as $row) {
                $url = trim($row['Url'] ?? '');

                if ($url === '') {
                    $skippedNoUrl++;
                    continue;
                }

                // 同じURLのブックマークが既にあれば再利用、なければ新規作成
                $bookmark = Bookmark::where('user_id', $user->id)
                    ->where('url', $url)
                    ->first();

                if ($bookmark) {
                    $bookmarksReused++;
                } else {
                    $bookmark = Bookmark::create([
                        'user_id' => $user->id,
                        'url' => $url,
                        'title' => trim($row['Title'] ?? '') ?: $url,
                    ]);
                    $bookmarksCreated++;
                }

                $bookmark->credentials()->create([
                    'user_id' => $user->id,
                    'label' => trim($row['Title'] ?? '') ?: null,
                    'username' => trim($row['Username'] ?? ''),
                    'password_encrypted' => $row['Password'] ?? '', // encryptedキャストで自動暗号化
                    'notes' => trim($row['Notes'] ?? '') ?: null,
                ]);

                $created++;
            }
        });

        $this->info("完了: 認証情報 {$created}件を作成しました");
        $this->line("  - ブックマーク新規作成: {$bookmarksCreated}件");
        $this->line("  - 既存ブックマーク再利用: {$bookmarksReused}件");
        $this->line("  - URLなしでスキップ: {$skippedNoUrl}件");

        return self::SUCCESS;
    }

    /**
     * タブ区切り/カンマ区切りを自動判定してパースする。
     * OTPAuth, Favorite, Archived, Tags 列は読み込むが使用しない(無視)。Notesはcredentials.notesに取り込む。
     *
     * @return array<int, array<string, string>>|null
     */
    private function parseCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return null;
        }

        $firstLine = fgets($handle);
        rewind($handle);

        // ヘッダー行のタブ/カンマの出現数で区切り文字を判定
        $delimiter = substr_count($firstLine, "\t") > substr_count($firstLine, ',') ? "\t" : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        if (! $header) {
            fclose($handle);
            return null;
        }

        $header = array_map('trim', $header);
        $requiredColumns = ['Title', 'Url', 'Username', 'Password'];
        foreach ($requiredColumns as $col) {
            if (! in_array($col, $header, true)) {
                fclose($handle);
                return null;
            }
        }

        $rows = [];
        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($line) === 1 && $line[0] === null) {
                continue; // 空行スキップ
            }
            // 列数がヘッダーと合わない行はゆるく合わせる(不足分はnull埋め)
            $line = array_pad($line, count($header), null);
            $rows[] = array_combine($header, $line);
        }

        fclose($handle);
        return $rows;
    }
}
