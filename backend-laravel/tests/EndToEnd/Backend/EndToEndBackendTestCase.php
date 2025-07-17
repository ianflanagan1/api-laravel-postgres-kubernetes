<?php

declare(strict_types=1);

namespace Tests\EndToEnd\Backend;

use Tests\TestCase as BaseTestCase;

abstract class EndToEndBackendTestCase extends BaseTestCase
{
    protected static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $defaultUrl = 'http://web:8080';
        $envUrl = getenv('E2E_TEST_BASE_URL_BACKEND');
        $url = is_string($envUrl) && $envUrl !== '' ? $envUrl : $defaultUrl;

        self::$baseUrl = rtrim($url, '/');
    }
}
