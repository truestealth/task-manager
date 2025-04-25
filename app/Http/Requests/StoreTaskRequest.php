<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTaskRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для выполнения этого запроса.
     */
    public function authorize(): bool
    {
        // Используем политику TaskPolicy для проверки права на создание
        $user = $this->user();

        return $user !== null && $user->can('create', Task::class);
    }

    /**
     * Получает правила валидации, которые применяются к запросу.
     *
     * @return array<string, string|array<int, mixed>|Rule>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
        ];
    }
}
