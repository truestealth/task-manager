<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Repositories\TaskRepository;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskIndexRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskCollectionResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
/**
 * @OA\Info(
 *     title="Task API",
 *     version="1.0.0",
 *     description="API для управления задачами"
 * )
 */
use Illuminate\Support\Facades\Cache;

final class TaskController extends Controller
{
    // Инициализация репозитория задач
    public function __construct(
        private readonly TaskRepository $taskRepository
    ) {}

    // Список задач с фильтрацией, пагинацией и кешированием
    /**
     * Получение списка задач с возможностью фильтрации
     *
     * @OA\Get(
     *     path="/api/tasks",
     *     summary="Получить список задач",
     *     tags={"Tasks"},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Фильтр по статусу задачи",
     *
     *         @OA\Schema(type="string", enum={"new", "in_progress", "completed"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="due_date",
     *         in="query",
     *         description="Фильтр по точной дате выполнения (Y-m-d)",
     *
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Parameter(
     *         name="due_date_after",
     *         in="query",
     *         description="Фильтр по дате после указанной (Y-m-d)",
     *
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Parameter(
     *         name="due_date_before",
     *         in="query",
     *         description="Фильтр по дате до указанной (Y-m-d)",
     *
     *         @OA\Schema(type="string", format="date")
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Поиск по названию или описанию",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список задач",
     *
     *         @OA\JsonContent(ref="#/components/schemas/TaskCollectionResource")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Требуется авторизация"
     *     )
     * )
     */
    public function index(TaskIndexRequest $request): TaskCollectionResource
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $filters = $request->filters();
        $perPage = $request->integer('per_page', 15);

        // Кэширование результатов на 10 минут
        $cacheKey = 'tasks.'.$user->id.'.'.md5($request->fullUrl());

        $store = Cache::getStore();
        if (method_exists($store, 'tags')) {
            $tasks = Cache::tags(['tasks', 'user:'.$user->id])->remember($cacheKey, 600, fn (): \Illuminate\Pagination\LengthAwarePaginator => $this->taskRepository->getFilteredTasks((int) $user->id, $filters, $perPage));
        } else {
            $tasks = Cache::remember($cacheKey, 600, fn (): \Illuminate\Pagination\LengthAwarePaginator => $this->taskRepository->getFilteredTasks((int) $user->id, $filters, $perPage));
        }

        return new TaskCollectionResource($tasks);
    }

    // Создание новой задачи текущим пользователем
    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     summary="Create new task",
     *     tags={"Tasks"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/StoreTaskRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Task created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/TaskResource")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        /** @var \App\Models\User $user */
        $user = auth()->user();
        // Автоматически добавляем user_id текущего пользователя
        $validatedData = array_merge($validatedData, ['user_id' => (int) $user->id]);

        $task = $this->taskRepository->create($validatedData);

        return response()->json(new TaskResource($task->load('user')), 201);
    }

    // Получение конкретной задачи (с проверкой прав)
    /**
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     summary="Получить информацию о задаче",
     *     tags={"Tasks"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID задачи",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Информация о задаче",
     *
     *         @OA\JsonContent(ref="#/components/schemas/TaskResource")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Задача не найдена"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Требуется авторизация"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Доступ запрещен"
     *     )
     * )
     */
    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load('user'));
    }

    // Обновление задачи (только для владельца)
    /**
     * Обновление задачи
     *
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     summary="Обновить задачу",
     *     tags={"Tasks"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID задачи",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/UpdateTaskRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Задача обновлена",
     *
     *         @OA\JsonContent(ref="#/components/schemas/TaskResource")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Задача не найдена"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Требуется авторизация"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Доступ запрещен"
     *     )
     * )
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $validatedData = $request->validated();

        /** @var array{
         *     title?: string,
         *     description?: string|null,
         *     due_date?: string|null,
         *     status?: string|null
         * } $validatedData
         */
        $task = $this->taskRepository->update($task, $validatedData);

        return new TaskResource($task->load('user'));
    }

    // Удаление задачи
    /**
     * Удаление задачи
     *
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     summary="Удалить задачу",
     *     tags={"Tasks"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID задачи",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Задача успешно удалена"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Задача не найдена"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Требуется авторизация"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Доступ запрещен"
     *     )
     * )
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskRepository->delete($task);

        return response()->json(null, 204);
    }
}
