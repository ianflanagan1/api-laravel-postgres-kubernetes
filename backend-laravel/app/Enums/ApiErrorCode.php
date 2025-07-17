<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\LogService;

enum ApiErrorCode: int
{
    /**
     * @param  array<string, scalar>  $placeholders  Array of placeholders with text to inject into the message.
     */
    public function message(array $placeholders = []): string
    {
        $message = match ($this) {
            self::UNAUTHORIZED_GENERAL          => 'Unauthorized',
            self::UNAUTHORIZED_LOGIN_FAILED     => 'Login failed',
            self::FORBIDDEN                     => 'Forbidden',
            self::NOT_FOUND                     => 'Not found',
            self::HTTP_METHOD_NOT_ALLOWED       => 'Method not allowed',
            self::RATE_LIMITED                  => 'Rate limited',
            self::VALIDATION_GENERAL            => 'Validation failed',
            self::VALIDATION_REQUIRED           => '`:field` is required',
            self::VALIDATION_MAX                => '`:field` must be `:max` characters or less',
            self::VALIDATION_MIN                => '`:field` must be `:min` characters or more',
            self::VALIDATION_NUMBER_MAX         => '`:field` must be less than or equal to `:max`',
            self::VALIDATION_NUMBER_MIN         => '`:field` must be greater than or equal to `:min`',
            self::VALIDATION_INVALID_FORMAT     => '`:field` format is invalid',
            self::VALIDATION_UNIQUE             => '`:field` has already been taken',
            self::VALIDATION_STRING             => '`:field` must be a string',
            self::VALIDATION_INTEGER            => '`:field` must be an integer',
            self::VALIDATION_EMAIL              => '`:field` must be a valid email',
            self::VALIDATION_PASSWORD           => 'Password failed validation. Include at least one lower case character, upper case character, number and symbol',
            self::VALIDATION_CONFIRMED          => '`:field` must match its confirmation',
            self::VALIDATION_REGEX              => '`:field` :advice',
            self::UNKNOWN                       => 'Server error',
        };

        foreach ($placeholders as $key => $value) {
            $message = str_replace(":$key", (string) $value, $message);
        }

        // Check for missing placeholders
        if (preg_match('/:[a-zA-Z0-9_]+/', $message, $matches)) {
            //  :               Literal
            //  [a-zA-Z0-9_]    Alpha-numeric character
            //  +               One or more such characters

            $missing = implode(', ', $matches);
            LogService::error("Missing placeholders for: {$missing} in {$this->name}: {$this->value}");
        }


        return $message;
    }
    // General Errors
    case UNAUTHORIZED_GENERAL = 40100;
    case UNAUTHORIZED_LOGIN_FAILED = 40101;
    case FORBIDDEN = 40300;
    case NOT_FOUND = 40400;
    case HTTP_METHOD_NOT_ALLOWED = 40500;
    case RATE_LIMITED = 42900;
    case VALIDATION_GENERAL = 42200;
    case VALIDATION_REQUIRED = 42201;
    case VALIDATION_MAX = 42202;
    case VALIDATION_MIN = 42203;
    case VALIDATION_NUMBER_MAX = 42204;
    case VALIDATION_NUMBER_MIN = 42205;
    case VALIDATION_INVALID_FORMAT = 42206; // not used yet
    case VALIDATION_UNIQUE = 42207;
    case VALIDATION_STRING = 42212;
    case VALIDATION_INTEGER = 42208;
    case VALIDATION_EMAIL = 42209;
    case VALIDATION_PASSWORD = 42210;
    case VALIDATION_CONFIRMED = 42211;
    case VALIDATION_REGEX = 42213;
    case UNKNOWN = 50000;
}
