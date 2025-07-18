<?php

declare(strict_types=1);

namespace App\Exceptions\Handlers;

use App\Enums\ApiErrorCode;
use App\Services\ApiResponseBuilder;
use App\Services\LogService;
use App\Services\ValidationErrorsBuilder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class ApiExceptionHandler
{
    public function register(Exceptions $exceptions): void
    {
        // Stop default logging
        $exceptions->report(function (Throwable $e) {
            return false;
        });

        // Handle exceptions
        $exceptions
            ->render(function (AuthenticationException $e): JsonResponse {
                // LogService::exception($e, 'AuthenticationException', [], true);
                return ApiResponseBuilder::error(ApiErrorCode::UNAUTHORIZED_GENERAL, SymfonyResponse::HTTP_UNAUTHORIZED);
            })

            ->render(function (MethodNotAllowedHttpException $e): JsonResponse {
                return ApiResponseBuilder::error(ApiErrorCode::HTTP_METHOD_NOT_ALLOWED, SymfonyResponse::HTTP_METHOD_NOT_ALLOWED);
            })

            ->render(function (ThrottleRequestsException $e): JsonResponse {
                return ApiResponseBuilder::error(ApiErrorCode::RATE_LIMITED, SymfonyResponse::HTTP_TOO_MANY_REQUESTS);
            })

            ->render(function (ValidationException $e): JsonResponse {
                /** @var \Illuminate\Validation\Validator $validator */
                $validator = $e->validator;

                return ApiResponseBuilder::errors(
                    ValidationErrorsBuilder::transform($validator),
                    SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            })

            ->render(function (HttpException $e): JsonResponse {
                $status = $e->getStatusCode();

                switch ($status) {
                    case SymfonyResponse::HTTP_FORBIDDEN:           return ApiResponseBuilder::error(ApiErrorCode::FORBIDDEN, $status);
                    case SymfonyResponse::HTTP_NOT_FOUND:           return ApiResponseBuilder::error(ApiErrorCode::NOT_FOUND, $status);
                    case SymfonyResponse::HTTP_TOO_MANY_REQUESTS:   return ApiResponseBuilder::error(ApiErrorCode::RATE_LIMITED, $status);
                    default:
                        LogService::exception($e, 'HttpException');

                        return ApiResponseBuilder::error(ApiErrorCode::UNKNOWN, $status);
                }
            })

            ->render(function (Throwable $e): JsonResponse {
                LogService::exception($e, 'Throwable');

                return ApiResponseBuilder::error(ApiErrorCode::UNKNOWN, SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
            });
    }
}
