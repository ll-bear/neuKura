<?php

namespace App\Jobs;

use App\Models\Bookmark;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FetchFaviconJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $bookmarkId)
    {
    }

    public function handle(): void
    {
        $bookmark = Bookmark::find($this->bookmarkId);

        if (! $bookmark || ! $bookmark->url) {
            return;
        }

        // Bookmarkモデルの domain アクセサ(parse_url済み)を利用
        $domain = $bookmark->domain ?? $bookmark->url;

        try {
            $response = Http::timeout(5)->get('https://www.google.com/s2/favicons', [
                'domain' => $domain,
                'sz' => 64,
            ]);

            if ($response->successful() && strlen($response->body()) > 0) {
                $path = "favicons/{$bookmark->id}.png";
                Storage::disk('public')->put($path, $response->body());

                $bookmark->forceFill([
                    'favicon_path' => $path,
                    'favicon_fetched_at' => now(),
                ])->save();
            }
        } catch (\Throwable $e) {
            Log::warning("Favicon fetch failed for bookmark {$this->bookmarkId}: {$e->getMessage()}");
        }
    }
}
