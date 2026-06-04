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

        $imageUrl = $this->extractImageUrl($html, $url);

        return compact('title', 'text', 'imageUrl');
    }

    private function extractImageUrl(string $html, string $pageUrl): ?string
    {
        $patterns = [
            '/<meta[^>]+property=["\']og:image(?::secure_url)?["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image(?::secure_url)?["\']/i',
            '/<meta[^>]+name=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image(?::src)?["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                $resolved = $this->resolveUrl(trim(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8')), $pageUrl);

                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        return null;
    }

    private function resolveUrl(string $url, string $baseUrl): ?string
    {
        if ($url === '' || str_starts_with($url, 'data:')) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $parts = parse_url($baseUrl);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = "{$parts['scheme']}://{$parts['host']}".(isset($parts['port']) ? ":{$parts['port']}" : '');

        if (str_starts_with($url, '//')) {
            return "{$parts['scheme']}:{$url}";
        }

        if (str_starts_with($url, '/')) {
            return $origin.$url;
        }

        $path = $parts['path'] ?? '/';
        $directory = str_ends_with($path, '/') ? $path : dirname($path).'/';

        return $origin.$directory.ltrim($url, '/');
    }
}
