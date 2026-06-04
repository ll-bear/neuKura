<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaService
{
    private string $baseUrl;

    private string $model;

    public function __construct()
    {
        $this->baseUrl = config('webPlot.llm_api_url');
        $this->model = config('webPlot.llm_model');
    }

    public function summarizeAndCategorize(string $text, array $categories): array
    {
        $categoryList = collect($categories)
            ->map(fn ($c) => "{$c['id']}: {$c['name']}")
            ->join("\n");

        $prompt = <<<PROMPT
以下のWebページの本文を読んで、JSONのみで返してください。余分な文字は不要です。

# 本文
{$text}

# カテゴリ一覧
{$categoryList}

# 出力形式
{
  "summary": "100文字程度の日本語要約",
  "category_id": 最も適切なカテゴリのID（数値）
}
PROMPT;

        $timeout = (int) config('webPlot.llm_chat_timeout', 120);

        $response = Http::timeout($timeout)->post("{$this->baseUrl}/api/chat", [
            'model' => $this->model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'stream' => false,
        ]);

        $content = $response->json('message.content', '{}');
        preg_match('/\{.*\}/s', $content, $matches);

        return json_decode($matches[0] ?? '{}', true) ?? [];
    }

    public function embed(string $text): array
    {
        $timeout = (int) config('webPlot.llm_embed_timeout', 180);

        $response = Http::timeout($timeout)->post("{$this->baseUrl}/api/embed", [
            'model' => $this->model,
            'input' => $text,
        ]);

        return $response->json('embeddings.0') ?? [];
    }
}
