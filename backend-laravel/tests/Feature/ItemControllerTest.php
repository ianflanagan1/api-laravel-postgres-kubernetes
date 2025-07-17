<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\Item;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;
use Tests\Traits\ChecksApiResponse;
use Tests\Traits\ConvertArrayToJsonStructure;

class ItemControllerTest extends TestCase
{
    use ChecksApiResponse, ConvertArrayToJsonStructure;

    protected const string BASE_ROUTE = '/api/v1/items';

    protected const string TABLE = 'items';

    protected const int PER_PAGE_DEFAULT = 10;

    protected const int PAGE_DEFAULT = 1;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // INDEX ////////////////////////////////////////////////////////////////////////////////

    /**
     * @return list<array{
     *      itemCount: int,
     *      params: array{
     *          page?: int,
     *          per_page?: int,
     *      },
     *      expectedCount: int,
     * }>
     */
    public static function cases_to_test_index_gets_paginated_items(): array
    {
        return [
            // Full page
            [
                'itemCount' => 25,
                'params' => [
                    'page' => 1,
                    'per_page' => 10,
                ],
                'expectedCount' => 10,
            ],
            // Incomplete last page
            [
                'itemCount' => 25,
                'params' => [
                    'page' => 3,
                    'per_page' => 10,
                ],
                'expectedCount' => 5, // Only 5 Items left
            ],
            // Different per_page
            [
                'itemCount' => 25,
                'params' => [
                    'page' => 1,
                    'per_page' => 18,
                ],
                'expectedCount' => 18,
            ],
        ];
    }

    /**
     * @param array{
     *     page?: int,
     *     per_page?: int
     * } $params
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_index_gets_paginated_items')]
    public function test_index_gets_paginated_items(int $itemCount, array $params, int $expectedCount): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $items = Item::factory()->count($itemCount)->create(['user_id' => $user->id]);

        $uri = self::BASE_ROUTE.$this->getIndexQueryString($params);

        // If parameters not set, apply defaults
        if (! isset($params['page'])) {
            $params['page'] = self::PAGE_DEFAULT;
        }

        if (! isset($params['per_page'])) {
            $params['per_page'] = self::PER_PAGE_DEFAULT;
        }

        $params['page'] = (int) $params['page'];
        $params['per_page'] = (int) $params['per_page'];

        $expectedItems = $items
            ->skip(($params['page'] - 1) * $params['per_page'])
            ->take($params['per_page'])
            ->pluck('uuid') // internal `uuid` = external `id`
            ->map(function ($uuid) {
                assert($uuid instanceof UuidInterface);

                return (string) $uuid;
            })
            ->toArray();

        $response = $this->getJson($uri);

        $response->assertOk()
            ->assertExactJsonStructure([
                'data' => [
                    '*' => self::itemMinimalStructure(),
                ],
                'pagination' => self::paginationStructure(),
                'meta' => self::metaStructure(),
            ])
            ->assertJsonCount($expectedCount, 'data')
            ->assertJson([
                'pagination' => [
                    'current_page' => $params['page'],
                    'per_page' => $params['per_page'],
                ],
            ]);

        // Check it returned the right Items in the right order
        $data = $response->json('data');

        if (! is_array($data)) {
            $this->fail('`data` is not an array: '.json_encode($response->json('data')));
        }

        $responseItems = collect($data)->pluck('id')->toArray(); // internal `uuid` = external `id`

        $this->assertEquals(
            $expectedItems,
            $responseItems,
        );
    }

    /**
     * @return list<array{
     *      params: array{
     *          page?: scalar,
     *          per_page?: scalar,
     *      },
     *      expectedStatus: int,
     *      expectedErrors: list<array{0: ApiErrorCode, 1?: string}>
     * }>
     */
    public static function cases_to_test_index_returns_error_on_failed_validation(): array
    {
        return [
            [
                'params' => [
                    'page' => 0,        // <-- Fail validation
                    'per_page' => 10,
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_NUMBER_MIN, 'page'],
                ],
            ],
            [
                'params' => [
                    'page' => 1,
                    'per_page' => 0,    // <-- Fail validation
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_NUMBER_MIN, 'page'],
                ],
            ],
            [
                'params' => [
                    'page' => 0,        // <-- Fail validation
                    'per_page' => 22,   // <-- Fail validation
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_NUMBER_MIN, 'page'],
                    [ApiErrorCode::VALIDATION_NUMBER_MAX, 'per_page'],
                ],
            ],
            [
                'params' => [
                    'page' => '5a',     // <-- Fail validation
                    'per_page' => 12.5, // <-- Fail validation
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_INTEGER, 'page'],
                    [ApiErrorCode::VALIDATION_INTEGER, 'per_page'],
                ],
            ],
        ];
    }

    /**
     * @param array{
     *     page?: scalar,
     *     per_page?: scalar
     * } $params
     * @param  list<array{0: ApiErrorCode, 1?: string}>  $expectedErrors,
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_index_returns_error_on_failed_validation')]
    public function test_index_returns_error_on_failed_validation(array $params, int $expectedStatus, array $expectedErrors): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $uri = self::BASE_ROUTE.$this->getIndexQueryString($params);

        $response = $this->getJson($uri);

        $this->checkErrors($response, $expectedStatus, $expectedErrors);
    }

    public function test_unauthenticated_user_cannot_access_index(): void
    {
        $params = [
            'page' => 1,
            'per_page' => 10,
        ];

        $uri = self::BASE_ROUTE.$this->getIndexQueryString($params);

        $response = $this->getJson($uri);
        $response->assertUnauthorized();
    }

    /**
     * @param array{
     *      page?: scalar,
     *      per_page?: scalar,
     * } $params
     */
    protected function getIndexQueryString(array &$params): string
    {
        if (empty($params)) {
            return '';
        }

        return '?'.http_build_query($params);
    }

    // STORE ////////////////////////////////////////////////////////////////////////////////

    public function test_store_creates_an_item(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $response = $this->postJson(self::BASE_ROUTE, $attributes);

        $expectedStructure = $attributes;
        $expectedStructure['id'] = 'just-for-the-structure';

        $response->assertStatus(SymfonyResponse::HTTP_CREATED)
            ->assertExactJsonStructure([
                'data' => self::convertArrayToJsonStructure($expectedStructure),
                'meta' => self::metaStructure(),
            ])
            ->assertJsonFragment($attributes);

        $this->assertDatabaseHas(self::TABLE, $attributes);
    }

    /**
     * @return list<array{
     *      attributes: array{
     *          name?: string,
     *          type?: string
     *      },
     *      expectedStatus: int,
     *      expectedErrors: list<array{0: ApiErrorCode, 1?: string}>
     * }>
     */
    public static function cases_to_test_store_returns_error_on_failed_validation(): array
    {
        return [
            // `name` missing
            [
                'attributes' => [
                    // 'name',                          // <-- Fail validation
                    'type' => Str::random(15),
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_REQUIRED, 'name'],
                ],
            ],
            // `name` too long
            [
                'attributes' => [
                    'name' => Str::random(100), // <-- Fail validation
                    'type' => Str::random(15),
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_MAX, 'name'],
                ],
            ],
        ];
    }

    /**
     * @param array{
     *      name?: string,
     *      type?: string
     * } $attributes
     * @param  list<array{0: ApiErrorCode, 1?: string}>  $expectedErrors
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_store_returns_error_on_failed_validation')]
    public function test_store_returns_error_on_failed_validation(array $attributes, int $expectedStatus, array $expectedErrors): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $response = $this->postJson(self::BASE_ROUTE, $attributes);

        $this->checkErrors($response, $expectedStatus, $expectedErrors);

        $this->assertDatabaseMissing(self::TABLE, $attributes);
    }

    public function test_store_respects_unique_constraint(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $originalAttributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $item = $user->items()->create($originalAttributes);

        $attributes = [
            'name' => $item->name,              // <-- Fail validation (should be unique)
            'type' => Str::random(15),
        ];

        $response = $this->postJson(self::BASE_ROUTE, $attributes);

        $this->checkErrors(
            $response,
            SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            [[ApiErrorCode::VALIDATION_UNIQUE, 'name']],
        );

        $this->assertDatabaseMissing(self::TABLE, $attributes);
    }

    public function test_unauthenticated_user_cannot_access_store(): void
    {
        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $response = $this->postJson(self::BASE_ROUTE, $attributes);
        $response->assertUnauthorized();
    }

    // SHOW /////////////////////////////////////////////////////////////////////////////////

    public function test_show_gets_an_item(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $item = $user->items()->create($attributes);

        $response = $this->getJson(self::BASE_ROUTE.'/'.$item->uuid);

        $attributes['id'] = $item->uuid; // internal `uuid` = external `id`

        $response->assertOk()
            ->assertExactJsonStructure([
                'data' => self::convertArrayToJsonStructure($attributes),
                'meta' => self::metaStructure(),
            ])
            ->assertJsonFragment(['data' => $attributes]);
    }

    public function test_show_fails_for_non_existent_item(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $response = $this->getJson(self::BASE_ROUTE.'/'.Str::uuid());

        $this->checkErrors($response, SymfonyResponse::HTTP_NOT_FOUND, [[ApiErrorCode::NOT_FOUND]]);
    }

    public function test_unauthenticated_user_cannot_access_show(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $item = $user->items()->create($attributes);

        $this->app['auth']->forgetGuards();

        $response = $this->getJson(self::BASE_ROUTE.'/'.$item->uuid);
        $response->assertUnauthorized();
    }

    // UPDATE ///////////////////////////////////////////////////////////////////////////////

    public function test_update_modifies_an_item(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $originalAttributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $item = $user->items()->create($originalAttributes);

        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $response = $this->putJson(self::BASE_ROUTE.'/'.$item->uuid, $attributes);

        $expectedItem = $attributes;
        $expectedItem['id'] = $item->uuid; // internal `uuid` = external `id`

        $response->assertOk()
            ->assertExactJsonStructure([
                'data' => self::convertArrayToJsonStructure($expectedItem),
                'meta' => self::metaStructure(),
            ])
            ->assertJsonFragment(['data' => $expectedItem]);

        $this->assertDatabaseMissing(self::TABLE, $originalAttributes);
        $this->assertDatabaseHas(self::TABLE, $attributes);
    }

    /**
     * @return list<array{
     *      attributes: array{
     *          name?: string,
     *          type?: string
     *      },
     *      expectedStatus: int,
     *      expectedErrors: list<array{0: ApiErrorCode, 1?: string}>
     * }>
     */
    public static function cases_to_test_update_returns_error_on_failed_validation(): array
    {
        return [
            // `name` missing
            [
                'attributes' => [
                    // 'name',                          // <-- Fail validation
                    'type' => Str::random(15),
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_REQUIRED, 'name'],
                ],
            ],
            // `name` too long
            [
                'attributes' => [
                    'name' => Str::random(100), // <-- Fail validation
                    'type' => Str::random(15),
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_MAX, 'name'],
                ],
            ],
        ];
    }

    /**
     * @param array{
     *      name?: string,
     *      type?: string
     * } $attributes
     * @param  list<array{0: ApiErrorCode, 1?: string}>  $expectedErrors
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_update_returns_error_on_failed_validation')]
    public function test_update_returns_error_on_failed_validation(array $attributes, int $expectedStatus, array $expectedErrors): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $originalAttributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $item = $user->items()->create($originalAttributes);

        $response = $this->putJson(self::BASE_ROUTE.'/'.$item->uuid, $attributes);

        $this->checkErrors($response, $expectedStatus, $expectedErrors);

        $this->assertDatabaseHas(self::TABLE, $originalAttributes);
        $this->assertDatabaseMissing(self::TABLE, $attributes);
    }

    public function test_update_respects_unique_constraint(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $firstItemAttributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $originalAttributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $firstItem = $user->items()->create($firstItemAttributes);
        $item = $user->items()->create($originalAttributes);

        $attributes = [
            'name' => $firstItem->name,         // <-- Fail validation (should be unique)
            'type' => Str::random(15),
        ];

        $response = $this->putJson(self::BASE_ROUTE.'/'.$item->uuid, $attributes);

        $this->checkErrors(
            $response,
            SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
            [[ApiErrorCode::VALIDATION_UNIQUE, 'name']],
        );

        // $this->assertDatabaseHas(self::TABLE, $originalAttributes); // todo: fix the problem in HandlesDbErrors handleDbErrors() the rollsback the outer transaction
        // $this->assertDatabaseMissing(self::TABLE, $attributes);
    }

    public function test_update_fails_for_non_existent_item(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $response = $this->putJson(self::BASE_ROUTE.'/'.Str::uuid(), $attributes);

        $this->checkErrors($response, SymfonyResponse::HTTP_NOT_FOUND, [[ApiErrorCode::NOT_FOUND]]);

        $this->assertDatabaseMissing(self::TABLE, $attributes);
    }

    public function test_unauthenticated_user_cannot_access_update(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $originalAttributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $item = $user->items()->create($originalAttributes);

        $this->app['auth']->forgetGuards();

        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $response = $this->putJson(self::BASE_ROUTE.'/'.$item->uuid, $attributes);
        $response->assertUnauthorized();
    }

    // DESTROY //////////////////////////////////////////////////////////////////////////////

    public function test_destroy_deletes_item(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $item = $user->items()->create($attributes);

        $response = $this->delete(self::BASE_ROUTE.'/'.$item->uuid);

        $expected = ['result' => 'success'];

        $response->assertOk()
            ->assertExactJsonStructure([
                'data' => self::convertArrayToJsonStructure($expected),
                'meta' => self::metaStructure(),
            ])
            ->assertJsonFragment(['data' => $expected]);

        $this->assertDatabaseMissing(self::TABLE, $attributes);
    }

    public function test_destroy_fails_for_non_existent_item(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $response = $this->delete(self::BASE_ROUTE.'/'.Str::uuid());

        $this->checkErrors($response, SymfonyResponse::HTTP_NOT_FOUND, [[ApiErrorCode::NOT_FOUND]]);
    }

    public function test_unauthenticated_user_cannot_access_destroy(): void
    {
        $user = $this->loggedInUser();
        Sanctum::actingAs($user);

        $attributes = [
            'name' => Str::random(10),
            'type' => Str::random(15),
        ];

        $item = $user->items()->create($attributes);

        $this->app['auth']->forgetGuards();

        $response = $this->delete(self::BASE_ROUTE.'/'.$item->uuid);
        $response->assertUnauthorized();
    }

    // COMMON METHODS ///////////////////////////////////////////////////////////////////////

    /**
     * @return list<string>
     */
    protected static function itemFullStructure(): array
    {
        return [
            'id',
            'name',
            'type',
        ];

        // Keep aligned with App\Http\Resources\ItemPublicFullResource->toArray();
    }

    /**
     * @return list<string>
     */
    protected static function itemMinimalStructure(): array
    {
        return [
            'id',
            'name',
        ];

        // Keep aligned with App\Http\Resources\ItemPublicMinimalResource->toArray();
    }
}
