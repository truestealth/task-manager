<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Task;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * Сопоставления моделей и политик для приложения.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Task::class => TaskPolicy::class,
    ];

    /**
     * Зарегистрировать любые службы аутентификации / авторизации.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
