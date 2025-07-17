<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    protected function loggedInUser(): User
    {
        return (new User)->newFromBuilder([ // Keep aligned with user in Tests\TestCase::loggedInUser()
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@user.com',
            'password' => bcrypt('12345678'),
        ]);
    }
}
