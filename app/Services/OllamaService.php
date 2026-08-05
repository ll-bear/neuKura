<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaService
{
    private string $baseUrl;
    private string $model;
    private string $embedModel;

    public function __construct()
    {
        $this->baseUrl = config('neuKura.llm_api_url');
        $this->model = config('neuKura.llm_model');
        $this->embedModel = config('neuKura.llm_embed_model');
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

        $timeout = (int) config('neuKura.llm_chat_timeout', 120);

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
        $timeout = (int) config('neuKura.llm_embed_timeout', 180);
        $model = $this->embedModel;

        $response = Http::timeout($timeout)->post("{$this->baseUrl}/api/embed", [
            'model' => $model,
            'input' => $text,
        ]);

        if (! $response->successful()) {
            logger()->error('Ollama embedding HTTP failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $model,
                'url' => "{$this->baseUrl}/api/embed",
            ]);

            return [];
        }

        $embedding = $response->json('embeddings.0');

        if (! is_array($embedding) || $embedding === []) {
            logger()->error('Ollama embedding response invalid', [
                'body' => $response->json(),
                'model' => $model,
            ]);

            return [];
        }

        return $embedding;
    }

    /*
    public function embed(string $text): array
    {
        $timeout = (int) config('neuKura.llm_embed_timeout', 180);

        $response = Http::timeout($timeout)->post("{$this->baseUrl}/api/embed", [
            'model' => $this->embedModel,
            'input' => $text,
        ]);

        if (! $response->successful()) {
            logger()->error('Ollama embedding failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $this->embedModel,
            ]);

            return [];
        }

        return $response->json('embeddings.0') ?? [];
    }
    */
}
