<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ScraperService
{
  public function scrape(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; BookmarkBot/1.0)',
        ])->timeout(15)->get($url);

        $html = $response->body();

        preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $titleMatch);
        $title = isset($titleMatch[1])
            ? html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES, 'UTF-8')
            : '';

        $text = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $text = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $text);
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim(mb_substr($text, 0, 3000));

        return compact('title', 'text');
    }
}