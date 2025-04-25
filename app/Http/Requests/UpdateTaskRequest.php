<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaskRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для выполнения этого запроса.
     */
    public function authorize(): bool
    {
        // Проверяем, что пользователь аутентифицирован и имеет право обновлять задачу
        $user = $this->user();
        /** @var Task|null $task */
        $task = $this->route('task');

        return $user !== null && $task instanceof Task && $user->can('update', $task);
    }

    /**
     * Получает правила валидации, которые применяются к запросу.
     *
     * @return array<string, string|array<int, mixed>|Rule>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'due_date' => 'sometimes|date',
            // Используем строковую валидацию in:
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed'])],
        ];
    }
}
