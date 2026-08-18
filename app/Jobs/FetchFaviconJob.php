<?php

namespace App\Jobs;

use App\Models\Credential;
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

    public function __construct(private int $credentialId)
    {
    }

    public function handle(): void
    {
        $credential = Credential::find($this->credentialId);

        if (! $credential || ! $credential->url) {
            return;
        }

        $domain = parse_url($credential->url, PHP_URL_HOST)
            ?? $credential->url; // スキームなしURLの場合の簡易フォールバック

        try {
            $response = Http::timeout(5)->get('https://www.google.com/s2/favicons', [
                'domain' => $domain,
                'sz' => 64,
            ]);

            if ($response->successful() && strlen($response->body()) > 0) {
                $path = "favicons/{$credential->id}.png";
                Storage::disk('public')->put($path, $response->body());

                $credential->forceFill([
                    'favicon_path' => $path,
                    'favicon_fetched_at' => now(),
                ])->save();
            }
        } catch (\Throwable $e) {
            Log::warning("Favicon fetch failed for credential {$this->credentialId}: {$e->getMessage()}");
        }
    }
}
