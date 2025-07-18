<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApiErrorCode;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Resources\UserPublicFullResource;
use App\Models\User;
use App\Services\ApiResponseBuilder;
use App\Traits\HandlesDbErrors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthController extends Controller
{
    use HandlesDbErrors;

    public function register(AuthRegisterRequest $request): JsonResponse
    {
        return $this->handleDbErrors(function () use ($request): JsonResponse {
            $user = User::create($request->validated());

            return ApiResponseBuilder::success(
                new UserPublicFullResource($user),
                SymfonyResponse::HTTP_CREATED
            );
        });
    }

    public function login(AuthLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /**
         * @var string $password
         *             Ensured by 'string' validation rule in \App\Http\Requests\AuthLoginRequest
         */
        $password = $request->validated()['password'];

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            return ApiResponseBuilder::error(
                ApiErrorCode::UNAUTHORIZED_LOGIN_FAILED,
                SymfonyResponse::HTTP_UNAUTHORIZED,
            );
        }

        $token = $user->createToken($user->name);

        return ApiResponseBuilder::success(['token' => $token->plainTextToken]);
    }

    public function logout(Request $request): JsonResponse
    {
        /**
         * @var User $user
         *           Ensured by ApiAuthMiddleware::class in authenticated endpoints
         */
        $user = $request->user();

        $user->tokens()->delete();

        return ApiResponseBuilder::success();
    }
}
