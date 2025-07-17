<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\ContentLengthMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContentLengthMiddlewareTest extends TestCase
{
    protected const string TEST_ROUTE = '/content-length-middleware-test-route';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return list<array{bytes: int, statusCode: int}>
     */
    public static function cases_to_test_it_adds_content_length_header_to_response(): array
    {
        return [
            [
                'bytes' => 0,
                'statusCode' => 200,
            ],
            [
                'bytes' => 0,
                'statusCode' => 402,
            ],
            [
                'bytes' => 100,
                'statusCode' => 200,
            ],
            [
                'bytes' => 100,
                'statusCode' => 402,
            ],
            [
                'bytes' => 10000,
                'statusCode' => 200,
            ],
            [
                'bytes' => 10000,
                'statusCode' => 402,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_it_adds_content_length_header_to_response')]
    public function test_it_adds_content_length_header_to_response(int $bytes, int $statusCode): void
    {
        Route::middleware(ContentLengthMiddleware::class)
            ->get(self::TEST_ROUTE, fn () => response(Str::random($bytes), $statusCode));

        $response = $this->getJson(self::TEST_ROUTE);

        $response->assertHeader('Content-Length', $bytes);

        $content = $response->getContent();
        if ($content === false) {
            $content = '';
        }

        $this->assertEquals(
            $bytes,
            strlen($content),
        );
    }
}
