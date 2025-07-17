<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemIndexRequest extends FormRequest
{
    protected const int PER_PAGE_DEFAULT = 10;

    protected const int PER_PAGE_MIN = 1;

    protected const int PER_PAGE_MAX = 20;

    protected const int PAGE_DEFAULT = 1;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['integer', 'min:' . self::PER_PAGE_MIN, 'max:' . self::PER_PAGE_MAX],
            'page' => ['integer', 'min:1'],
        ];
    }

    /**
     * Get the validated `per_page` value.
     */
    public function getPerPage(): int
    {
        /**
         * @var int $value
         * Ensured by `integer` validation rule in rules() above
         * */
        $value = $this->validated('per_page');

        return (int) $value;
    }

    /**
     * Get the validated `page` value.
     */
    public function getPage(): int
    {
        /**
         * @var int $value
         * Ensured by `integer` validation rule in rules() above
         * */
        $value = $this->validated('page');

        return (int) $value;
    }

    /**
     * Prepare the data for validation.
     *
     * This method is called automatically before the 'rules()' method.
     * It allows you to modify the request data before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', self::PER_PAGE_DEFAULT),
            'page' => $this->input('page', self::PAGE_DEFAULT),
        ]);
    }
}
