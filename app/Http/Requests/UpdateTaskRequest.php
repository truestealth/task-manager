<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="UpdateTaskRequest",
 *     type="object",
 *
 *     @OA\Property(property="title", type="string", maxLength=255, example="Updated task title"),
 *     @OA\Property(property="description", type="string", example="Updated description"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-05-01"),
 *     @OA\Property(property="status", type="string", enum={"new", "in_progress", "completed"}, example="completed")
 * )
 */
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
     */
    /**
     * @return array<string, mixed>
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
