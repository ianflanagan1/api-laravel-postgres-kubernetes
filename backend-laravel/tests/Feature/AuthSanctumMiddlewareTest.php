<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthSanctumMiddlewareTest extends TestCase
{
    protected const string TEST_ROUTE = '/auth-sanctum-middleware-test-route';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get(self::TEST_ROUTE, function () {
            return response()->json([
                'data' => [
                    'user_id' => auth()->id(),
                ],
            ]);
        })->middleware('auth:sanctum');
    }

    public function test_authenticated_user_can_access_protected_route(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson(self::TEST_ROUTE);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'user_id' => $user->id,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->getJson(self::TEST_ROUTE);

        $response->assertUnauthorized(); // 401
        // $response->assertJson([
        //     'message' => 'Unauthenticated.', // Laravel's default unauthenticated message for API requests
        // ]);
    }
}
