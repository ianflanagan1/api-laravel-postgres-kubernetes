<?php

declare(strict_types=1);

namespace Tests\EndToEnd\Backend;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class PrometheusMetricsTest extends EndToEndBackendTestCase
{
    protected string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        $endpoint = is_string(Config::get('prometheus.urls.default'))
            ? Config::get('prometheus.urls.default')
            : 'prometheus';

        $this->endpoint = '/'.ltrim($endpoint, '/');
    }

    public function test_prometheus_metrics_are_not_publicly_accessible(): void
    {

        $url = self::$baseUrl.$this->endpoint;

        $response = Http::get($url);

        $this->assertSame(
            403,
            $response->status(),
            'Status was not 403 on '.$this->endpoint
        );
    }
}
