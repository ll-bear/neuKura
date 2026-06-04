<?php

namespace Tests\Unit;

use App\Services\ScraperService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScraperServiceTest extends TestCase
{
    #[DataProvider('ogImageProvider')]
    public function test_extract_image_url_from_meta_tags(string $html, string $pageUrl, ?string $expected): void
    {
        $service = new ScraperService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractImageUrl');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($service, $html, $pageUrl));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: ?string}>
     */
    public static function ogImageProvider(): array
    {
        return [
            'og:image' => [
                '<meta property="og:image" content="https://cdn.example.com/hero.jpg">',
                'https://example.com/article',
                'https://cdn.example.com/hero.jpg',
            ],
            'relative path' => [
                '<meta property="og:image" content="/images/cover.png">',
                'https://example.com/blog/post',
                'https://example.com/images/cover.png',
            ],
            'missing' => [
                '<title>Example</title>',
                'https://example.com',
                null,
            ],
        ];
    }
}
