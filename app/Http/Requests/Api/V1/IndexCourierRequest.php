<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Courier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCourierRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 15;

    public const int MAX_PER_PAGE = 100;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Turn the comma separated level filter (?level=2,3) into a list so that
     * every level can be validated on its own.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('level'))) {
            $levels = array_map('trim', explode(',', $this->input('level')));

            $this->merge(['level' => array_values(array_filter($levels, 'strlen'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', 'array'],
            'level.*' => ['integer', 'between:'.Courier::MIN_LEVEL.','.Courier::MAX_LEVEL],
            'sort' => ['nullable', Rule::in(Courier::SORTABLE_COLUMNS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,'.self::MAX_PER_PAGE],
        ];
    }
}
