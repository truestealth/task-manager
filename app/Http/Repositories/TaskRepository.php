<?php

declare(strict_types=1);

namespace App\Http\Repositories;

use App\Models\Task;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class TaskRepository
{
    /**
     * Получить отфильтрованный список задач пользователя
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Task>
     */
    public function getFilteredTasks(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::query()->where('user_id', $userId);

        // Фильтрация по статусу
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Фильтрация по точной дате выполнения
        if (isset($filters['due_date'])) {
            $dueDate = $filters['due_date'];
            if (is_string($dueDate) || $dueDate instanceof DateTimeInterface) {
                $query->whereDate('due_date', $dueDate);
            }
        }

        // Фильтрация по дате ПОСЛЕ указанной
        if (isset($filters['due_date_after'])) {
            $dueDateAfter = $filters['due_date_after'];
            if (is_string($dueDateAfter) || $dueDateAfter instanceof DateTimeInterface) {
                $query->whereDate('due_date', '>=', $dueDateAfter);
            }
        }

        // Фильтрация по дате ДО указанной
        if (isset($filters['due_date_before'])) {
            $dueDateBefore = $filters['due_date_before'];
            if (is_string($dueDateBefore) || $dueDateBefore instanceof DateTimeInterface) {
                $query->whereDate('due_date', '<=', $dueDateBefore);
            }
        }

        // Поиск по названию или описанию
        if (isset($filters['search']) && is_string($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->with('user')->paginate($perPage);
    }

    /**
     * Создать новую задачу
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Task
    {
        $userId = isset($data['user_id']) && is_numeric($data['user_id']) ? (int) $data['user_id'] : 0;
        $task = Task::create($data);
        $this->clearTasksCache($userId);

        return $task;
    }

    /**
     * Обновить существующую задачу
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        $userId = (int) $task->user_id;
        $this->clearTasksCache($userId);

        return $task;
    }

    /**
     * Удалить задачу
     */
    public function delete(Task $task): bool
    {
        $userId = (int) $task->user_id;
        $result = $task->delete();
        $this->clearTasksCache($userId);

        return (bool) $result;
    }

    /**
     * Очистка кэша задач пользователя
     */
    private function clearTasksCache(int $userId): void
    {
        $store = Cache::getStore();
        if (method_exists($store, 'tags')) {
            Cache::tags(['tasks', 'user:'.$userId])->flush();
        } else {
            Cache::flush();
        }
    }
}
