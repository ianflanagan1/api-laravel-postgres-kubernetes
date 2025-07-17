<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;
use Tests\Traits\ChecksApiResponse;
use Tests\Traits\ConvertArrayToJsonStructure;

class AuthControllerTest extends TestCase
{
    use ChecksApiResponse, ConvertArrayToJsonStructure;

    protected const string REGISTER_ROUTE = '/api/v1/register';

    protected const string LOGIN_ROUTE = '/api/v1/login';

    protected const string LOGOUT_ROUTE = '/api/v1/logout';

    protected const string TABLE = 'users';

    protected const string PAT_TABLE = 'personal_access_tokens';

    protected function setUp(): void
    {
        parent::setUp();
    }

    // REGISTER /////////////////////////////////////////////////////////////////////////////

    public function test_register_creates_user(): void
    {
        $password = '1234abcdE!';

        $attributes = [
            'name' => Str::random(10),
            'email' => Str::random(10).'@'.Str::random(10).'.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $response = $this->postJson(self::REGISTER_ROUTE, $attributes);

        unset($attributes['password']);
        unset($attributes['password_confirmation']);

        $response->assertStatus(SymfonyResponse::HTTP_CREATED)
            ->assertJsonStructure([
                'data' => self::userMinimalStructure(),
                'meta' => self::metaStructure(),
            ])
            ->assertJson([
                'data' => $attributes,
            ]);

        $this->assertDatabaseHas(self::TABLE, $attributes);
    }

    /**
     * @return list<array{
     *      attributes: array{
     *          name?: string,
     *          email?: string,
     *          password?: string,
     *          password_confirmation?: string,
     *      },
     *      expectedStatus: int,
     *      expectedErrors: list<array{0: ApiErrorCode, 1?: string}>
     * }>
     */
    public static function cases_to_test_registration_returns_error_on_failed_validation(): array
    {
        return [
            [
                'attributes' => [
                    // 'name' => '',                             // <-- Fail validation (required)
                    'email' => 'abc@def.com',
                    'password' => '1234abcdE!',
                    'password_confirmation' => '1234abcdE!',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_REQUIRED, 'name'],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'a',                              // <-- Fail validation (min)
                    'email' => 'abcdef.com',                    // <-- Fail validation (email)
                    'password' => '1234abcdE!',
                    // 'password_confirmation' => '',           // <-- Fail validation (required)
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_MIN, 'name'],
                    [ApiErrorCode::VALIDATION_EMAIL, 'email'],
                    [ApiErrorCode::VALIDATION_CONFIRMED, 'password'],
                ],
            ],
            [
                'attributes' => [
                    'name' => Str::random(300),                 // <-- Fail validation (max)
                    'email' => Str::random(300).'@def.com',   // <-- Fail validation (max)
                    'password' => '1234abcdE!',
                    'password_confirmation' => 'abcdE!1234',            // <-- Fail validation (confirmed)
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_MAX, 'name'],
                    [ApiErrorCode::VALIDATION_MAX, 'email'],
                    [ApiErrorCode::VALIDATION_CONFIRMED, 'password'],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'abc',
                    // 'email' => 'abc@def.com',            // <-- Fail validation (required)
                    'password' => '1234aE!',                // <-- Fail validation (min)
                    'password_confirmation' => '1234aE!',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_REQUIRED, 'email'],
                    [ApiErrorCode::VALIDATION_MIN, 'password'],
                    [ApiErrorCode::VALIDATION_PASSWORD],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'abc',
                    'email' => 'abc@def.com',
                    'password' => str_repeat('a', 300).'1234aE!', // <-- Fail validation (max)
                    'password_confirmation' => str_repeat('a', 300).'1234aE!',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_MAX, 'password'],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'abc',
                    'email' => 'abc@def.com',
                    'password' => ' 1234abcdE!',                // <-- Fail validation (regex - leading whitespace)
                    'password_confirmation' => ' 1234abcdE!',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_REGEX, 'leading'],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'abc',
                    'email' => 'abc@def.com',
                    'password' => '1234abcdE! ',                // <-- Fail validation (regex - trailing whitespace)
                    'password_confirmation' => '1234abcdE! ',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_REGEX, 'trailing'],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'abc',
                    'email' => 'abc@def.com',
                    'password' => '1234abcdE',              // <-- Fail validation (password - no symbol)
                    'password_confirmation' => '1234abcdE',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_PASSWORD],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'abc',
                    'email' => 'abc@def.com',
                    'password' => 'abcdefghE!',              // <-- Fail validation (password - no number)
                    'password_confirmation' => 'abcdefghE!',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_PASSWORD],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'abc',
                    'email' => 'abc@def.com',
                    'password' => '1234abcd!',              // <-- Fail validation (password - no uppercase)
                    'password_confirmation' => '1234abcd!',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_PASSWORD],
                ],
            ],
            [
                'attributes' => [
                    'name' => 'abc',
                    'email' => 'abc@def.com',
                    'password' => '12345678E!',              // <-- Fail validation (password - no lowercase)
                    'password_confirmation' => '12345678E!',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_PASSWORD],
                ],
            ],
        ];
    }

    /**
     * @param array{
     *      name?: string,
     *      email?: string,
     *      password?: string,
     *      password_confirmation?: string,
     * } $attributes
     * @param  list<array{0: ApiErrorCode, 1?: string}>  $expectedErrors,
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_registration_returns_error_on_failed_validation')]
    public function test_registration_returns_error_on_failed_validation(array $attributes, int $expectedStatus, array $expectedErrors): void
    {
        $response = $this->postJson(self::REGISTER_ROUTE, $attributes);

        $this->checkErrors($response, $expectedStatus, $expectedErrors);

        unset($attributes['password']);
        unset($attributes['password_confirmation']);

        $this->assertDatabaseMissing(self::TABLE, $attributes);
    }

    // SHOW /////////////////////////////////////////////////////////////////////////////////

    public function test_login_gets_a_token(): void
    {
        $attributes = [
            'name' => Str::random(10),
            'email' => Str::random(10).'@'.Str::random(10).'.com',
            'password' => '1234abcdE!',
        ];

        $user = User::create($attributes);

        $post = $attributes;
        unset($post['name']);

        $response = $this->postJson(self::LOGIN_ROUTE, $post);

        $response->assertOk()
            ->assertExactJsonStructure([
                'data' => [
                    'token',
                ],
                'meta' => self::metaStructure(),
            ]);

        $this->assertIsArray($response['data']);

        $token = $response['data']['token'];

        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^\d+\|[A-Za-z0-9]+$/', $token);
        $this->assertDatabaseHas(self::PAT_TABLE, self::getTokenAttributes($token));
    }

    /**
     * @return list<array{
     *      attributes: array{
     *          email?: string,
     *          password?: string
     *      },
     *      expectedStatus: int,
     *      expectedErrors: list<array{0: ApiErrorCode, 1?: string}>
     * }>
     */
    public static function cases_to_test_login_fails_for_incorrect_login_data(): array
    {
        return [
            [
                'attributes' => [
                    // 'email'     => 'correct@email.com',      // <-- Fail validation (required)
                    'password' => 'correctABC123@',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_REQUIRED, 'email'],
                ],
            ],
            [
                'attributes' => [
                    'email' => 'correct@email.com',
                    // 'password'  => 'correctABC123@',         // <-- Fail validation (required)
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrors' => [
                    [ApiErrorCode::VALIDATION_REQUIRED, 'password'],
                ],
            ],
            [
                'attributes' => [
                    'email' => 'incorrect@email.com',       // <-- Mismatch (email)
                    'password' => 'correctABC123@',
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNAUTHORIZED,
                'expectedErrors' => [
                    [ApiErrorCode::UNAUTHORIZED_LOGIN_FAILED],
                ],
            ],
            [
                'attributes' => [
                    'email' => 'correct@email.com',
                    'password' => 'incorrectABC123@',          // <-- Mismatch (password)
                ],
                'expectedStatus' => SymfonyResponse::HTTP_UNAUTHORIZED,
                'expectedErrors' => [
                    [ApiErrorCode::UNAUTHORIZED_LOGIN_FAILED],
                ],
            ],
        ];
    }

    /**
     * @param array{
     *      email?: string,
     *      password?: string
     * } $attributes
     * @param  list<array{0: ApiErrorCode, 1?: string}>  $expectedErrors
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases_to_test_login_fails_for_incorrect_login_data')]
    public function test_login_fails_for_incorrect_login_data(array $attributes, int $expectedStatus, array $expectedErrors): void
    {
        $originalAttributes = [
            'name' => Str::random(10),
            'email' => 'correct@email.com',
            'password' => 'correctABC123@',
        ];

        $user = User::create($originalAttributes);

        $response = $this->postJson(self::LOGIN_ROUTE, $attributes);

        $this->checkErrors($response, $expectedStatus, $expectedErrors);
    }

    // DESTROY //////////////////////////////////////////////////////////////////////////////

    public function test_logout_deletes_tokens(): void
    {
        $attributes = [
            'name' => Str::random(10),
            'email' => 'correct@email.com',
            'password' => 'correctABC123@',
        ];

        $user = User::create($attributes);
        Sanctum::actingAs($user);

        $token = $user->createToken($user->name);

        $post = $attributes;
        unset($post['name']);

        $expected = ['result' => 'success'];

        $response = $this->postJson(self::LOGOUT_ROUTE, $post);

        $response->assertOk()
            ->assertExactJsonStructure([
                'data' => self::convertArrayToJsonStructure($expected),
                'meta' => self::metaStructure(),
            ])
            ->assertJsonFragment(['data' => $expected]);

        $this->assertDatabaseMissing(self::PAT_TABLE, self::getTokenAttributes($token->plainTextToken));
    }

    public function test_unauthenticated_user_cannot_access_destroy(): void
    {
        $response = $this->postJson(self::LOGOUT_ROUTE);
        $response->assertUnauthorized();
    }

    // COMMON METHODS ///////////////////////////////////////////////////////////////////////

    /**
     * @return list<string>
     */
    protected static function userFullStructure(): array
    {
        return [
            'name',
            'email',
        ];

        // Keep aligned with App\Http\Resources\UserPublicFullResource->toArray();
    }

    /**
     * @return list<string>
     */
    protected static function userMinimalStructure(): array
    {
        return [
            'name',
        ];

        // Keep aligned with App\Http\Resources\UserPublicMinimalResource->toArray();
    }

    /**
     * @return array{id: string, token: string}
     */
    protected static function getTokenAttributes(string $token): array
    {
        [$id, $splitToken] = explode('|', $token, 2);
        $hashedToken = hash('sha256', $splitToken);

        return [
            'id' => $id,
            'token' => $hashedToken,
        ];
    }
}
