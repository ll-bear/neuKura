<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ScraperService
{
    public function scrape(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->timeout(15)->get($url);

        $html = $this->convertToUtf8($response->body());

        $title = $this->getMetaContent($html, 'og:title');
        $imageUrl = $this->getMetaContent($html, 'og:image');
        $description = $this->getMetaContent($html, 'og:description');

        // titleが空なら<title>タグから取得
        if (empty($title)) {
            preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m);
            $title = isset($m[1])
                ? html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8')
                : '';
        }

        // 本文抽出（script/style除去）
        $text = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $text = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $text);
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim(mb_substr($text, 0, 3000));

        // 本文が短ければog:descriptionで補完
        if (mb_strlen($text) < 100 && !empty($description)) {
            $text = $description;
        }

        // http → https に変換
        $imageUrl = str_replace('http://', 'https://', $imageUrl);

        return compact('title', 'text', 'imageUrl');
    }

    private function convertToUtf8(string $html): string
    {
        // metaタグから文字コードを検出
        preg_match('/<meta[^>]+charset=["\']?([a-zA-Z0-9\-]+)/si', $html, $m);
        $charset = strtolower(trim($m[1] ?? ''));

        if ($charset && !in_array($charset, ['utf-8', 'utf8'])) {
            $converted = mb_convert_encoding($html, 'UTF-8', $charset);
            if ($converted !== false) {
                return $converted;
            }
        }

        // metaタグで検出できない場合
        $detected = mb_detect_encoding($html, ['UTF-8', 'SJIS', 'EUC-JP', 'ISO-2022-JP'], true);
        if ($detected && $detected !== 'UTF-8') {
            $converted = mb_convert_encoding($html, 'UTF-8', $detected);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $html;
    }

    // OGP情報取得
    private function getMetaContent(string $html, string $property): string
    {
        // property → content の順
        preg_match(
            '/<meta[^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]+content=["\'](.*?)["\']/si',
            $html, $m1
        );

        // content → property の順（逆パターン）
        preg_match(
            '/<meta[^>]+content=["\'](.*?)["\'"][^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]*/si',
            $html, $m2
        );

        $result = $m1[1] ?? $m2[1] ?? '';

        // HTMLエンティティをデコード（&amp; → & など）
        return html_entity_decode($result, ENT_QUOTES, 'UTF-8');
    }
}
