<?php

declare(strict_types=1);

namespace Tests\EndToEnd\Backend;

use Tests\Traits\TestHttpHeaders;

class HttpCompressionTest extends EndToEndBackendTestCase
{
    use TestHttpHeaders;

    protected const LARGE_ENDPOINT = '/1280b';

    protected const SMALL_ENDPOINT = '/1279b';

    protected const GUZZLE_CONTENT_ENCODING_HEADER = 'x-encoded-content-encoding';

    /**
     * @return list<array{
     *      requestHeaders: array<string, string|null>,
     *      assertHeadersIncluded: array<string, string|null>,
     *      assertHeadersExcluded: list<string>
     * }>
     */
    public static function cases_to_test_brotli_or_gzip_enabled_when_requested(): array
    {
        return [
            [
                'requestHeaders' => ['Accept-Encoding' => 'br'],
                'assertHeadersIncluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER => 'br'],
                'assertHeadersExcluded' => [],
            ],
            [
                'requestHeaders' => ['Accept-Encoding' => 'gzip'],
                'assertHeadersIncluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER => 'gzip'],
                'assertHeadersExcluded' => [],
            ],
            [
                'requestHeaders' => ['Accept-Encoding' => 'deflate'],
                'assertHeadersIncluded' => [],
                'assertHeadersExcluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER],
            ],
            [
                'requestHeaders' => ['Accept-Encoding' => 'identity'],
                'assertHeadersIncluded' => [],
                'assertHeadersExcluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER],
            ],
            [
                'requestHeaders' => ['Accept-Encoding' => ''],
                'assertHeadersIncluded' => [],
                'assertHeadersExcluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER],
            ],
            [
                'requestHeaders' => ['Accept-Encoding' => null],
                'assertHeadersIncluded' => [],
                'assertHeadersExcluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER],
            ],
        ];
    }

    /**
     * @return list<array{
     *      requestHeaders: array<string, string|null>,
     *      assertHeadersIncluded: array<string, string|null>,
     *      assertHeadersExcluded: list<string>
     * }>
     */
    public static function cases_to_test_brotli_preferred_over_gzip(): array
    {
        return [
            [
                'requestHeaders' => ['Accept-Encoding' => 'br, gzip'],
                'assertHeadersIncluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER => 'br'],
                'assertHeadersExcluded' => [],
            ],
            [
                'requestHeaders' => ['Accept-Encoding' => 'gzip, br'],
                'assertHeadersIncluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER => 'br'],
                'assertHeadersExcluded' => [],
            ],
        ];
    }

    /**
     * @return list<array{
     *      requestHeaders: array<string, string|null>,
     *      assertHeadersIncluded: array<string, string|null>,
     *      assertHeadersExcluded: list<string>
     * }>
     */
    public static function cases_to_test_http_compression_disabled_for_small_files(): array
    {
        return [
            [
                'requestHeaders' => ['Accept-Encoding' => 'br'],
                'assertHeadersIncluded' => [],
                'assertHeadersExcluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER],
            ],
            [
                'requestHeaders' => ['Accept-Encoding' => 'gzip'],
                'assertHeadersIncluded' => [],
                'assertHeadersExcluded' => [self::GUZZLE_CONTENT_ENCODING_HEADER],
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param  array<string, string|null>  $requestHeaders
     * @param  array<string, string|null>  $assertHeadersIncluded
     * @param  list<string>  $assertHeadersExcluded
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_brotli_or_gzip_enabled_when_requested')]
    public function test_brotli_or_gzip_enabled_when_requested(
        array $requestHeaders,
        array $assertHeadersIncluded,
        array $assertHeadersExcluded
    ): void {
        $this->checkResponseHeaders(
            self::$baseUrl.self::LARGE_ENDPOINT,
            $assertHeadersIncluded,
            $assertHeadersExcluded,
            $requestHeaders,
        );
    }

    /**
     * @param  array<string, string|null>  $requestHeaders
     * @param  array<string, string|null>  $assertHeadersIncluded
     * @param  list<string>  $assertHeadersExcluded
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_brotli_preferred_over_gzip')]
    public function test_brotli_preferred_over_gzip(
        array $requestHeaders,
        array $assertHeadersIncluded,
        array $assertHeadersExcluded
    ): void {
        $this->checkResponseHeaders(
            self::$baseUrl.self::LARGE_ENDPOINT,
            $assertHeadersIncluded,
            $assertHeadersExcluded,
            $requestHeaders,
        );
    }

    /**
     * @param  array<string, string|null>  $requestHeaders
     * @param  array<string, string|null>  $assertHeadersIncluded
     * @param  list<string>  $assertHeadersExcluded
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_http_compression_disabled_for_small_files')]
    public function test_http_compression_disabled_for_small_files(
        array $requestHeaders,
        array $assertHeadersIncluded,
        array $assertHeadersExcluded
    ): void {
        $this->checkResponseHeaders(
            self::$baseUrl.self::SMALL_ENDPOINT,
            $assertHeadersIncluded,
            $assertHeadersExcluded,
            $requestHeaders,
        );
    }
}
