<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ApiErrorCode;

final class ApiError
{
    public string $message;

    /**
     * @param  ApiErrorCode  $code  The numerical error code.
     * @param  array<string, scalar>  $placeholders  Array of placeholders with text to inject into the human-readable error message.
     */
    public function __construct(
        public readonly ApiErrorCode $code = ApiErrorCode::UNKNOWN,     // Align this with \App\Services\ApiResponseBuilder::error default value
        array $placeholders = [],
    ) {
        $this->message = $this->code->message($placeholders);
    }

    /**
     * @return array{code: int, message: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code->value,
            'message' => $this->message,
        ];
    }
}
