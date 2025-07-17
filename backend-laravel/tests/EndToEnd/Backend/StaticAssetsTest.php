<?php

declare(strict_types=1);

namespace Tests\EndToEnd\Backend;

use Illuminate\Support\Facades\Http;

class StaticAssetsTest extends EndToEndBackendTestCase
{
    /**
     * @return list<array{path: string}>
     */
    public static function cases_to_test_static_assets_are_served(): array
    {
        return [
            [
                'path' => '/robots.txt',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_static_assets_are_served')]
    public function test_static_assets_are_served(string $path): void
    {
        $url = self::$baseUrl.$path;

        $response = Http::get($url);

        $this->assertSame(
            200,
            $response->status(),
            "Status was not 200 on '{$path}'"
        );
    }
}
