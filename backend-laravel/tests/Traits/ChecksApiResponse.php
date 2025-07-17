<?php

namespace Tests\Traits;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;

trait ChecksApiResponse
{
    /**
     * @return list<string>
     */
    protected static function paginationStructure(): array
    {
        return [
            'current_page',
            'per_page',
            'total',
            'last_page',
        ];
    }

    /**
     * @return list<string>
     */
    protected static function metaStructure(): array
    {
        return [
            'request_id',
            'timestamp',
            'duration',
        ];
    }

    /**
     * @return list<string>
     */
    protected static function errorStructure(): array
    {
        return [
            'code',
            'message',
        ];
    }

    /**
     * @param  TestResponse<JsonResponse>  $response
     * @param  list<array{0: ApiErrorCode, 1?: string}>  $expectedErrors
     */
    protected function checkErrors(TestResponse $response, int $expectedStatus, array $expectedErrors): void
    {
        // Check HTTP status and response structure
        $response->assertStatus($expectedStatus)
            ->assertExactJsonStructure([
                'errors' => [
                    '*' => self::errorStructure(),
                ],
                'meta' => self::metaStructure(),
            ]);

        $responseErrors = $response['errors'];
        $this->assertIsArray($responseErrors);

        // Assert the number of errors match
        $this->assertSame(
            count($expectedErrors),
            count($responseErrors),
            'Unexpected number of errors in '.json_encode($responseErrors)
        );

        // For each expected error, assert its `code` is present. If a `search` term is specified, check the `code` is paired with a `message` that contains the `search` term
        foreach ($expectedErrors as $expectedError) {
            $code = $expectedError[0]->value;
            $search = $expectedError[1] ?? null;

            $found = false;

            foreach ($responseErrors as $responseError) {

                $this->assertIsArray($responseError);
                $this->assertIsInt($responseError['code']);
                $this->assertIsString($responseError['message']);

                if ($responseError['code'] === $code) {
                    if ($search === null || str_contains($responseError['message'], $search)) {
                        $found = true;
                        break;
                    }
                }
            }

            $this->assertTrue(
                $found,
                "Didn't find `{$code}` with `{$search}` in: ".json_encode($responseErrors)
            );
        }
    }
}
