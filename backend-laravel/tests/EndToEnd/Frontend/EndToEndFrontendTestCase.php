<?php

declare(strict_types=1);

namespace Tests\EndToEnd\Frontend;

use Tests\TestCase as BaseTestCase;

abstract class EndToEndFrontendTestCase extends BaseTestCase
{
    protected static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $defaultUrl = 'http://frontend:8080';
        $envUrl = getenv('E2E_TEST_BASE_URL_FRONTEND');
        $url = is_string($envUrl) && $envUrl !== '' ? $envUrl : $defaultUrl;

        self::$baseUrl = rtrim($url, '/');
    }
}
