<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TaskIndexRequest extends FormRequest
{
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|string|in:new,in_progress,completed',
            'due_date' => 'sometimes|date_format:Y-m-d',
            'due_date_after' => 'sometimes|date_format:Y-m-d',
            'due_date_before' => 'sometimes|date_format:Y-m-d',
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Get the filters array from the validated data.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = [
            'status' => $this->input('status'),
            'due_date' => $this->input('due_date'),
            'due_date_after' => $this->input('due_date_after'),
            'due_date_before' => $this->input('due_date_before'),
            'search' => $this->input('search'),
        ];

        // Удаляем null-значения для строгого array shape
        return array_filter($filters, static fn (mixed $v): bool => $v !== null);
    }
}
