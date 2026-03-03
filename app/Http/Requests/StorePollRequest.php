<?php

namespace App\Http\Requests;

use App\DTOs\CreatePollDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Trim whitespace from string inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => strip_tags(trim((string) $this->title)),
            'description' => strip_tags(trim((string) $this->description)),
            'options' => array_map(fn ($o): string => strip_tags(trim((string) $o)), $this->options ?? []),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'min:1', 'max:255', 'distinct'],
        ];
    }

    public function toDTO(): CreatePollDTO
    {
        return new CreatePollDTO(
            title: $this->validated('title'),
            description: $this->validated('description'),
            options: $this->validated('options')
        );
    }
}
