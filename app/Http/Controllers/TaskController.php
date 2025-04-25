<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class TaskController extends Controller
{
    /**
     * Получение списка задач с возможностью фильтрации
     *
     * @return Collection<int, Task>
     */
    public function index(Request $request): Collection
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $query = Task::query()->where('user_id', $user->id);

        // Фильтрация по статусу
        if ($request->has('status') && is_string($request->status)) {
            $query->where('status', $request->status);
        }

        // Фильтрация по точной дате выполнения
        if ($request->has('due_date') && is_string($request->due_date)) {
            // Сравнение только по дате, без времени
            $query->whereDate('due_date', $request->due_date);
        }

        // Фильтрация по дате ПОСЛЕ указанной
        if ($request->has('due_date_after') && is_string($request->due_date_after)) {
            $query->whereDate('due_date', '>', $request->due_date_after);
        }

        // Фильтрация по дате ДО указанной
        if ($request->has('due_date_before') && is_string($request->due_date_before)) {
            $query->whereDate('due_date', '<', $request->due_date_before);
        }

        // Кэширование результатов на 10 минут
        $cacheKey = 'tasks.'.$user->id.'.'.md5($request->fullUrl());

        /** @var Collection<int, Task> */
        $tasks = Cache::remember($cacheKey, 600, fn () =>
            /** @var Collection<int, Task> */
            $query->with('user')->get());

        return $tasks;
    }

    /**
     * Создание новой задачи
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        /** @var \App\Models\User $user */
        $user = auth()->user();
        // Автоматически добавляем user_id текущего пользователя
        $validatedData = array_merge($validatedData, ['user_id' => $user->id]);

        $task = Task::create($validatedData);
        // Очистка кэша после создания новой задачи
        Cache::tags('tasks')->flush();

        return response()->json($task->load('user'), 201);
    }

    /**
     * Получение информации о конкретной задаче
     */
    public function show(Task $task): Task
    {
        $this->authorize('view', $task);

        return $task->load('user');
    }

    /**
     * Обновление задачи
     */
    public function update(UpdateTaskRequest $request, Task $task): Task
    {
        $this->authorize('update', $task);

        $validatedData = $request->validated();

        $task->update($validatedData);
        // Очистка кэша после обновления задачи
        Cache::tags('tasks')->flush();

        return $task->load('user');
    }

    /**
     * Удаление задачи
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();
        // Очистка кэша после удаления задачи
        Cache::tags('tasks')->flush();

        return response()->json(null, 204);
    }
}
