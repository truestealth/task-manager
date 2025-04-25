<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('auth_token')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

test('список задач кэшируется в redis', function () {
    // Создаем задачи
    Task::factory()->count(3)->create(['user_id' => $this->user->id]);

    // Убеждаемся, что кэш пуст
    $cacheKey = 'tasks:all:user_'.$this->user->id;
    Cache::forget($cacheKey);

    // Первый запрос заполнит кэш
    $this->getJson('/api/tasks', $this->headers);

    // Проверяем, что данные закэшированы
    expect(Cache::has($cacheKey))->toBeTrue();

    // Добавляем новую задачу
    Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Новая задача после кэширования',
    ]);

    // Кэш должен очиститься после создания новой задачи через API
    $this->postJson('/api/tasks', [
        'title' => 'Еще одна новая задача',
        'description' => 'Описание новой задачи',
        'due_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => 'new',
    ], $this->headers);

    // Проверяем, что кэш очищен
    expect(Cache::has($cacheKey))->toBeFalse();

    // Делаем новый запрос
    $response = $this->getJson('/api/tasks', $this->headers);

    // Проверяем, что в ответе есть обе новые задачи
    $response->assertJsonFragment(['title' => 'Новая задача после кэширования']);
    $response->assertJsonFragment(['title' => 'Еще одна новая задача']);

    // Проверяем, что кэш снова заполнен
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('кэш очищается при обновлении задачи', function () {
    // Создаем задачу
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    // Первый запрос заполнит кэш
    $this->getJson('/api/tasks', $this->headers);

    // Проверяем, что кэш заполнен
    $cacheKey = 'tasks:all:user_'.$this->user->id;
    expect(Cache::has($cacheKey))->toBeTrue();

    // Обновляем задачу через API
    $this->putJson("/api/tasks/{$task->id}", [
        'title' => 'Обновленная задача',
        'status' => 'completed',
    ], $this->headers);

    // Проверяем, что кэш очищен после обновления
    expect(Cache::has($cacheKey))->toBeFalse();

    // Делаем новый запрос
    $response = $this->getJson('/api/tasks', $this->headers);

    // Проверяем, что в ответе есть обновленная задача
    $response->assertJsonFragment([
        'title' => 'Обновленная задача',
        'status' => 'completed',
    ]);

    // Проверяем, что кэш снова заполнен
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('кэш очищается при удалении задачи', function () {
    // Создаем задачу
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Задача для удаления',
    ]);

    // Первый запрос заполнит кэш
    $response = $this->getJson('/api/tasks', $this->headers);
    $response->assertJsonFragment(['title' => 'Задача для удаления']);

    // Проверяем, что кэш заполнен
    $cacheKey = 'tasks:all:user_'.$this->user->id;
    expect(Cache::has($cacheKey))->toBeTrue();

    // Удаляем задачу через API
    $this->deleteJson("/api/tasks/{$task->id}", [], $this->headers);

    // Проверяем, что кэш очищен после удаления
    expect(Cache::has($cacheKey))->toBeFalse();

    // Делаем новый запрос
    $response = $this->getJson('/api/tasks', $this->headers);

    // Проверяем, что в ответе нет удаленной задачи
    $response->assertDontSeeText('Задача для удаления');

    // Проверяем, что кэш снова заполнен
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('кэширование работает с разными параметрами фильтрации', function () {
    // Создаем задачи разных типов
    Task::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'status' => 'pending',
    ]);

    Task::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
    ]);

    // Запрос всех задач
    $this->getJson('/api/tasks', $this->headers);

    // Запрос задач со статусом "pending"
    $this->getJson('/api/tasks?status=pending', $this->headers);

    // Проверяем, что существуют оба кэша
    $allTasksKey = 'tasks:all:user_'.$this->user->id;
    $pendingTasksKey = 'tasks:'.md5(json_encode(['status' => 'pending'])).':user_'.$this->user->id;

    expect(Cache::has($allTasksKey) || Cache::has('tasks:'.md5(json_encode([])).':user_'.$this->user->id))->toBeTrue();
    expect(Cache::has($pendingTasksKey))->toBeTrue();

    // Создаем новую задачу - должны очиститься все кэши
    $this->postJson('/api/tasks', [
        'title' => 'Новая задача для проверки кэша',
        'description' => 'Описание',
        'due_date' => now()->format('Y-m-d H:i:s'),
        'status' => 'new',
    ], $this->headers);

    // Проверяем, что кэши очищены
    expect(Cache::has($allTasksKey))->toBeFalse();
    expect(Cache::has($pendingTasksKey))->toBeFalse();
});

test('кэширование ускоряет повторные запросы', function () {
    // Создаем много задач для нагрузки
    Task::factory()->count(50)->create(['user_id' => $this->user->id]);

    // Очищаем кэш
    $cacheKey = 'tasks:all:user_'.$this->user->id;
    Cache::forget($cacheKey);

    // Замеряем время первого запроса (без кэша)
    $startTime = microtime(true);
    $this->getJson('/api/tasks', $this->headers);
    $firstRequestTime = microtime(true) - $startTime;

    // Замеряем время второго запроса (с использованием кэша)
    $startTime = microtime(true);
    $this->getJson('/api/tasks', $this->headers);
    $cachedRequestTime = microtime(true) - $startTime;

    // Запрос с кэшированием должен быть быстрее
    expect($cachedRequestTime)->toBeLessThan($firstRequestTime);
});

test('к кэшированным данным нет доступа у других пользователей', function () {
    // Создаем задачи для текущего пользователя
    Task::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'title' => 'Задача первого пользователя',
    ]);

    // Первый запрос заполнит кэш
    $this->getJson('/api/tasks', $this->headers);

    // Создаем второго пользователя
    $secondUser = User::factory()->create();
    $secondToken = $secondUser->createToken('auth_token')->plainTextToken;

    // Создаем задачи для второго пользователя
    Task::factory()->count(2)->create([
        'user_id' => $secondUser->id,
        'title' => 'Задача второго пользователя',
    ]);

    // Делаем запрос от имени второго пользователя
    $response = $this->getJson('/api/tasks', [
        'Authorization' => 'Bearer '.$secondToken,
    ]);

    // Проверяем, что второй пользователь видит только свои задачи
    $response->assertJsonCount(2)
        ->assertJsonFragment(['title' => 'Задача второго пользователя'])
        ->assertDontSeeText('Задача первого пользователя');

    // Проверяем, что кэш для второго пользователя создан отдельно
    $firstUserCacheKey = 'tasks:all:user_'.$this->user->id;
    $secondUserCacheKey = 'tasks:all:user_'.$secondUser->id;

    expect(Cache::has($firstUserCacheKey))->toBeTrue();
    expect(Cache::has($secondUserCacheKey))->toBeTrue();
});
