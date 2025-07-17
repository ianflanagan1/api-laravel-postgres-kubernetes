<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;

trait TestHttpHeaders
{
    /**
     * @param  array<string, string|null>  $assertHeadersIncluded
     * @param  list<string>  $assertHeadersExcluded
     * @param  array<string, string|null>  $requestHeaders
     */
    protected function checkResponseHeaders(string $url, array $assertHeadersIncluded = [], array $assertHeadersExcluded = [], array $requestHeaders = []): void
    {
        $response = Http::withHeaders($requestHeaders)->get($url);

        $this->assertSame(
            200,
            $response->status(),
            "Status not 200 for Path: {$url}, Request headers: ".json_encode($requestHeaders),
        );

        foreach ($assertHeadersIncluded as $header => $expectedValue) {
            $this->assertTrue(
                $response->hasHeader($header),
                "'{$header}' header missing for Path: {$url}, Request headers: ".json_encode($requestHeaders),
            );

            if ($expectedValue !== null) {
                $actualValue = $response->getHeaderLine($header);

                $this->assertSame(
                    $expectedValue,
                    $actualValue,
                    "'{$header}' header was '{$actualValue}' instead of '{$expectedValue}' for Path: {$url}, Request headers: ".json_encode($requestHeaders),
                );
            }
        }

        foreach ($assertHeadersExcluded as $header) {
            $this->assertFalse(
                $response->hasHeader($header),
                "'{$header}: {$response->getHeaderLine($header)}' header wrongly included for Path: {$url}, Request headers: ".json_encode($requestHeaders),
            );
        }
    }
}
