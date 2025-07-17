<?php

declare(strict_types=1);

namespace Tests\EndToEnd\Frontend;

use Illuminate\Support\Facades\Http;
use Tests\Traits\TestHttpHeaders;

class HttpHeadersTest extends EndToEndFrontendTestCase
{
    use TestHttpHeaders;

    public const IMG_ENDPOINT = '/assets/logo-Bpq0hC1H.png';

    // public const GUZZLE_CONTENT_ENCODING_HEADER = 'x-encoded-content-encoding';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_index_html_response_headers_are_correct(): void
    {
        $url = self::$baseUrl.'/';

        $assertHeadersIncluded = [
            // Global
            'Strict-Transport-Security' => null,                    // HTTPS only
            'X-Content-Type-Options' => 'nosniff',                  // Respect Content-Type headers (prevent MIME confusion attacks)
            // Caching
            'Cache-Control' => null,
            // All HTML resources
            'Content-Type' => 'text/html; charset=UTF-8',           // Prevent XSS attacks
            'Referrer-Policy' => 'strict-origin-when-cross-origin', // Prevent data leak (browsers before 2014)
            'Content-Security-Policy' => null,
            'X-Frame-Options' => 'DENY',                            // Covered by CSP; Pre-2015 browsers
        ];

        $assertHeadersExcluded = [
            // 'Server',           // Don't reveal information
            'X-Powered-By',     // Don't reveal information
            'X-XSS-Protection', // Can create vulnerabilities
            'Expect-CT',
            'Public-Key-Pins',  // Deprecated
            'Pragma',           // HTTP/1.0
        ];

        $this->checkResponseHeaders(
            $url,
            $assertHeadersIncluded,
            $assertHeadersExcluded,
        );
    }
}
