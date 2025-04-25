<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Определяет, может ли пользователь просматривать какие-либо модели.
     */
    public function viewAny(): bool
    {
        return true;
    }

    /**
     * Определяет, может ли пользователь просматривать конкретную модель.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Определяет, может ли пользователь создавать модели.
     */
    public function create(): bool
    {
        return true;
    }

    /**
     * Определяет, может ли пользователь обновлять модель.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Определяет, может ли пользователь удалять модель.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
