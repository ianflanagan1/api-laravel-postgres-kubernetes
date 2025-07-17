<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\InjectMetaMiddleware;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;
use Tests\Traits\ChecksApiResponse;
use Tests\Traits\ConvertArrayToJsonStructure;

class InjectMetaMiddlewareTest extends TestCase
{
    use ChecksApiResponse, ConvertArrayToJsonStructure;

    protected const string TEST_ROUTE = '/inject-meta-middleware-test-route';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return list<array{
     *      content: array{data: mixed} | array{errors: list<array{code: int, message: string}>},
     *      statusCode: int
     * }>
     */
    public static function cases_to_test_it_adds_metadata_to_json_response(): array
    {
        return [
            [
                'content' => ['data' => ['item' => ['id' => '1234-abcd', 'name' => 'name1', 'type' => 'type1']]],
                'statusCode' => SymfonyResponse::HTTP_OK,
            ],
            [
                'content' => ['errors' => [['code' => 50000, 'message' => 'Something went wrong']]],
                'statusCode' => SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
            ],
        ];
    }

    /**
     * @param array{
     *      content: array{data: mixed} | array{errors: list<array{code: int, message: string}>},
     *      statusCode: int
     * } $content
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_it_adds_metadata_to_json_response')]
    public function test_it_adds_metadata_to_json_response(array $content, int $statusCode): void
    {
        Route::middleware(InjectMetaMiddleware::class)
            ->get(self::TEST_ROUTE, fn () => response()->json($content, $statusCode));

        $response = $this->getJson(self::TEST_ROUTE);

        $expectedStructure = self::convertArrayToJsonStructure($content);
        $expectedStructure['meta'] = self::metaStructure();

        $response->assertStatus($statusCode)
            ->assertExactJsonStructure($expectedStructure);
    }
}
