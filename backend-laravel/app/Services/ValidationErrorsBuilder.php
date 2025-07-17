<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ApiError;
use App\Enums\ApiErrorCode;
use Illuminate\Validation\Validator;

class ValidationErrorsBuilder
{
    /**
     * Transforms a ValidationException failed rules into an array of ApiError DTOs.
     *
     * @param  Validator  $validator  The Laravel validator instance containing failed validation rules
     * @return array<ApiError> Array of ApiError DTOs representing validation failures
     */
    public static function transform(Validator $validator): array
    {
        $errors = [];
        $failedFields = $validator->failed();

        // Transform each failure to ApiError and add to $errors array
        foreach ($failedFields as $field => $failures) {
            $fieldRules = $validator->getRules()[$field] ?? [];
            $fieldErrors = self::createErrorForField($field, $fieldRules, $failures);
            $errors = array_merge($errors, $fieldErrors);
        }

        // Ensure at least one validation error is returned
        if (! $errors) {
            LogService::error('No errors processed from failed validation', ['failedRules' => $failedFields]);
            $errors[] = new ApiError(ApiErrorCode::VALIDATION_GENERAL);
        }

        return $errors;
    }

    /**
     * Creates an ApiError for a specific field based on its failed rules.
     *
     * @param  string  $field  The field name that failed validation
     * @param  array<string>  $fieldRules  Array of all validation rules for the field
     * @param  array<string, list<string>>  $failures  Array of failed validation rules for the field
     * @return list<ApiError> Array of the ApiErrors created
     */
    protected static function createErrorForField(string $field, array $fieldRules, array $failures): array
    {
        $errors = [];

        foreach ($failures as $failure => $value) {
            $errors[] = match ($failure) {
                'Max'       => self::createMaxError($field, $fieldRules, (int) $value[0]),
                'Min'       => self::createMinError($field, $fieldRules, (int) $value[0]),
                'NotRegex'  => self::createNotRegexError($field, $value[0]),
                'Required'  => new ApiError(ApiErrorCode::VALIDATION_REQUIRED, ['field' => $field]),
                'Unique'    => new ApiError(ApiErrorCode::VALIDATION_UNIQUE, ['field' => $field]),
                'String'    => new ApiError(ApiErrorCode::VALIDATION_STRING, ['field' => $field]),
                'Integer'   => new ApiError(ApiErrorCode::VALIDATION_INTEGER, ['field' => $field]),
                'Email'     => new ApiError(ApiErrorCode::VALIDATION_EMAIL, ['field' => $field]),
                'Confirmed' => new ApiError(ApiErrorCode::VALIDATION_CONFIRMED, ['field' => $field]),
                'Illuminate\Validation\Rules\Password' => new ApiError(ApiErrorCode::VALIDATION_PASSWORD),
                default     => self::createErrorForUnhandledFailure($field, $failure, $failures),
            };
        }

        // It's reasonable to assume that each validation rule only appears once per field, so don't check for duplicate errors

        return $errors;
    }

    /**
     * Creates a general validation error and logs the fact that the failure didn't match scenario
     *
     * @param  array<string, list<string>>  $failures
     */
    protected static function createErrorForUnhandledFailure(string $field, string $failure, array $failures): ApiError
    {
        LogService::error(
            'Did not handle a match for `failure`',
            [
                'field' => $field,
                'failure' => $failure,
                'failures' => $failures,
            ]
        );
        return new ApiError(ApiErrorCode::VALIDATION_GENERAL);
    }

    /**
     * Creates a max validation error with appropriate type based on field type.
     *
     * @param  string  $field  The field name that failed validation
     * @param  array<string>  $fieldRules  Array of validation rules for the field
     * @param  int  $max  The maximum length of string, or max value for a number
     * @return ApiError The created max validation error
     */
    protected static function createMaxError(string $field, array $fieldRules, int $max): ApiError
    {
        $code = self::isNumber($fieldRules)
            ? ApiErrorCode::VALIDATION_NUMBER_MAX
            : ApiErrorCode::VALIDATION_MAX;

        return new ApiError($code, ['field' => $field, 'max' => $max]);
    }

    /**
     * Creates a min validation error with appropriate type based on field type.
     *
     * @param  string  $field  The field name that failed validation
     * @param  array<string>  $fieldRules  Array of validation rules for the field
     * @param  int  $min  The minimum length of string, or max value for a number
     * @return ApiError The created min validation error
     */
    protected static function createMinError(string $field, array $fieldRules, int $min): ApiError
    {
        $code = self::isNumber($fieldRules)
            ? ApiErrorCode::VALIDATION_NUMBER_MIN
            : ApiErrorCode::VALIDATION_MIN;

        return new ApiError($code, ['field' => $field, 'min' => $min]);
    }

    /**
     * Creates a regex validation error with appropriate message based on pattern.
     *
     * @param  string  $field  The field name that failed validation
     * @param  string  $pattern  The regex pattern failed
     * @return ApiError The created regex validation error
     */
    protected static function createNotRegexError(string $field, string $pattern): ApiError
    {
        switch ($pattern) {
            case '/^\s/': $advice = 'can\'t have leading whitespace';
                break;
            case '/\s$/': $advice = 'can\'t have trailing whitespace';
                break;
            default:
                LogService::error(
                    'Did not handle a match for regex `pattern`',
                    [
                        'field' => $field,
                        'pattern' => $pattern,
                    ]
                );
                return new ApiError(ApiErrorCode::VALIDATION_GENERAL);
        }

        return new ApiError(ApiErrorCode::VALIDATION_REGEX, ['field' => $field, 'advice' => $advice]);
    }

    /**
     * Determines if a field is numeric based on its validation rules.
     *
     * @param  array<string>  $rules  Array of validation rules to check
     * @return bool True if the field is numeric, false otherwise
     */
    protected static function isNumber(array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($rule == 'integer' || $rule == 'numeric') {
                return true;
            }
        }

        return false;
    }
}
