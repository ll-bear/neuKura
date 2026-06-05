<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ScraperService
{
    public function scrape(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->timeout(15)->get($url);

        $html = $response->body();

        // タイトル抽出（属性付きtitleタグに対応）
        preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $titleMatch);
        $title = isset($titleMatch[1])
            ? html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES, 'UTF-8')
            : '';

        // OGP description を本文として使う
        preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/si', $html, $descMatch);
        $ogDescription = $descMatch[1] ?? '';

        // OGP titleも試みる
        if (empty($title)) {
            preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\'](.*?)["\']/si', $html, $m);
            $title = $m[1] ?? '';
        }

        // OGP image
        preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/si', $html, $imageMatch);
        $imageUrl = $imageMatch[1] ?? '';

        // 本文抽出（script/style除去）
        $text = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $text = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $text);
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim(mb_substr($text, 0, 3000));

        // 本文が短い場合はOGP descriptionで補完
        if (mb_strlen($text) < 100 && !empty($ogDescription)) {
            $text = $ogDescription;
        }

        return compact('title', 'text', 'imageUrl');
    }
}
