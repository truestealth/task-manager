<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

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

        // Настройка Sanctum
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Настройка времени жизни токена
        Sanctum::authenticateAccessTokensUsing(fn (PersonalAccessToken $accessToken, bool $isValid): bool => $isValid && ($accessToken->expires_at === null || $accessToken->expires_at->isFuture()));

        // Настройка токена Sanctum для более длительного времени жизни
        Gate::define('viewWebhookDeliveries', fn (User $user): bool => in_array($user->email, [
            // Добавьте сюда email администраторов
        ]));
    }
}
