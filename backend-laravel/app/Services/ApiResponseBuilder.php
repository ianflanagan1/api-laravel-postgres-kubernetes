<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ApiError;
use App\Enums\ApiErrorCode;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiResponseBuilder
{
    /**
     * Return a success JSON response
     *
     * @param string|int|float|bool|array<string, mixed>|Arrayable<string, mixed>|JsonSerializable $data The data to include in the response
     * @param int  $status  The HTTP status code
     */
    public static function success(
        string|int|float|bool|array|Arrayable|JsonSerializable $data = ['result' => 'success'],
        int $status = SymfonyResponse::HTTP_OK
    ): JsonResponse {
        return response()->json(
            ['data' => $data],
            $status
        );
    }

    /**
     * Return a paginated JSON response with a resource collection
     *
     * @template TModel of Model
     *
     * @param LengthAwarePaginator<int, TModel> $paginator
     * @param class-string $resourceClass
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        int $status = SymfonyResponse::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'data' => $resourceClass::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], $status);
    }

    /**
     * Return JSON response with an array of errors
     *
     * @param array<ApiError> $errors Array of errors (arrays with message, followed by code)
     * @param int  $status The HTTP status code
     */
    public static function errors(
        array $errors = [new ApiError()],
        int $status = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR
    ): JsonResponse {
        return response()->json(
            ['errors' => $errors],
            $status
        );

        // Note: Hard-coded files mirror this json structure: ./public/4xx.json ./public/5xx.json etc.
    }

    /**
     * Return JSON response with an array of errors containing only one element
     *
     * @param ApiErrorCode $code Error code
     * @param int $status The HTTP status code
     */
    public static function error(
        ApiErrorCode $code = ApiErrorCode::UNKNOWN,                 // Align this with \App\DTOs\ApiError default value
        int $status = SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR
    ): JsonResponse {
        return self::errors(
            [new ApiError($code)],
            $status
        );
    }

    public static function not_found(): JsonResponse
    {
        return self::error(
            ApiErrorCode::NOT_FOUND,
            SymfonyResponse::HTTP_NOT_FOUND
        );
    }

    /**
     * Return a response with no body / content
     *
     * @param int $status The HTTP status code
     */
    // public static function noBody(int $status = SymfonyResponse::HTTP_NO_CONTENT): Response
    // {
    //     return response()->noContent($status);
    // }
}
