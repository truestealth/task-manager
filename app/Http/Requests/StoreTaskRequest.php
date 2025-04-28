<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="StoreTaskRequest",
 *     type="object",
 *     required={"title", "description", "due_date", "status"},
 *
 *     @OA\Property(property="title", type="string", maxLength=255, example="Test task"),
 *     @OA\Property(property="description", type="string", example="Test description"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-04-25"),
 *     @OA\Property(property="status", type="string", enum={"new", "in_progress", "completed"}, example="new"),
 *     @OA\Property(property="user_id", type="integer", example=1)
 * )
 */
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
     */
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date',
            'status' => ['required', Rule::in(['new', 'in_progress', 'completed'])],
            'user_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
