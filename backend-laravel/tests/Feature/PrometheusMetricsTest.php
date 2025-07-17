<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PrometheusMetricsTest extends TestCase
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

    public function test_prometheus_metrics_are_served(): void
    {
        // override ip restrictions for this test
        config(['prometheus.allowed_ips' => ['127.0.0.1']]);

        $response = $this->get($this->endpoint);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=UTF-8');
        $this->assertStringContainsString('# HELP ', (string) $response->getContent());
        $this->assertStringContainsString('# TYPE ', (string) $response->getContent());
        // $this->assertStringContainsString('laravel_request_duration_seconds', $response->getContent());
    }
}
