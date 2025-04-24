<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    /**
     * Получение списка задач с возможностью фильтрации
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Task::query();

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтрация по сроку выполнения
        if ($request->has('due_date')) {
            $query->where('due_date', '<=', $request->due_date);
        }

        // Кэширование результатов на 10 минут
        $cacheKey = 'tasks.' . md5($request->fullUrl());
        
        return Cache::remember($cacheKey, 600, function () use ($query) {
            return $query->with('user')->get();
        });
    }

    /**
     * Создание новой задачи
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Валидация входных данных
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,in_progress,completed',
            'user_id' => 'required|exists:users,id'
        ]);

        $task = Task::create($validated);
        // Очистка кэша после создания новой задачи
        Cache::tags('tasks')->flush();

        return response()->json($task, 201);
    }

    /**
     * Получение информации о конкретной задаче
     * @param Task $task
     * @return Task
     */
    public function show(Task $task)
    {
        return $task->load('user');
    }

    /**
     * Обновление задачи
     * @param Request $request
     * @param Task $task
     * @return Task
     */
    public function update(Request $request, Task $task)
    {
        // Валидация входных данных (все поля опциональны)
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,in_progress,completed',
            'user_id' => 'sometimes|exists:users,id'
        ]);

        $task->update($validated);
        // Очистка кэша после обновления задачи
        Cache::tags('tasks')->flush();

        return $task->load('user');
    }

    /**
     * Удаление задачи
     * @param Task $task
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Task $task)
    {
        $task->delete();
        // Очистка кэша после удаления задачи
        Cache::tags('tasks')->flush();

        return response()->json(null, 204);
    }
}
