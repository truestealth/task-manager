<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('auth_token')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

test('пользователь может получить список задач', function () {
    // Создаем задачи для пользователя
    Task::factory()->count(3)->create(['user_id' => $this->user->id]);

    // Выполняем запрос
    $response = $this->getJson('/api/tasks', $this->headers);

    // Проверяем ответ
    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJson(fn (AssertableJson $json) => $json->has(0, fn ($json) =>
                // Проверяем наличие всех ожидаемых полей, включая user
                $json->hasAll(['id', 'title', 'description', 'due_date', 'status', 'user_id', 'created_at', 'updated_at', 'user'])
                    ->whereType('user', 'array') // Убедимся, что user это массив
        )
        );
});

test('пользователь может создать новую задачу', function () {
    $taskData = [
        'title' => 'Тестовая задача',
        'description' => 'Описание тестовой задачи',
        'due_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => 'pending', // Используем правильный статус
    ];

    $response = $this->postJson('/api/tasks', $taskData, $this->headers);

    $response->assertStatus(201)
        ->assertJson(fn (AssertableJson $json) => $json->where('title', $taskData['title'])
            ->where('description', $taskData['description'])
            ->where('status', $taskData['status'])
            ->where('user_id', $this->user->id)
            ->etc()
        );

    $this->assertDatabaseHas('tasks', [
        'title' => $taskData['title'],
        'user_id' => $this->user->id,
    ]);
});

test('пользователь может получить информацию о своей задаче', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/tasks/{$task->id}", $this->headers);

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->where('id', $task->id)
            ->where('title', $task->title)
            ->where('description', $task->description)
            ->where('user_id', $this->user->id)
                // Проверяем наличие поля user
            ->has('user')
            ->whereType('user', 'array')
            ->etc()
        );
});

test('пользователь может обновить свою задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    $updateData = [
        'title' => 'Обновленная задача',
        'status' => 'completed',
    ];

    $response = $this->putJson("/api/tasks/{$task->id}", $updateData, $this->headers);

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->where('title', $updateData['title'])
            ->where('status', $updateData['status'])
                // Проверяем наличие поля user после обновления
            ->has('user')
            ->whereType('user', 'array')
            ->etc()
        );

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => $updateData['title'],
        'status' => $updateData['status'],
    ]);
});

test('пользователь может удалить свою задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/tasks/{$task->id}", [], $this->headers);

    $response->assertStatus(204);

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

test('пользователь не может получить доступ к задаче другого пользователя', function () {
    // Создаем другого пользователя и его задачу
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $otherUser->id]);

    // Выполняем запрос
    $response = $this->getJson("/api/tasks/{$task->id}", $this->headers);

    // Проверяем, что доступ запрещен
    $response->assertStatus(403);
});

test('пользователь не может обновить задачу другого пользователя', function () {
    // Создаем другого пользователя и его задачу
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $otherUser->id]);

    // Выполняем запрос
    $response = $this->putJson("/api/tasks/{$task->id}", [
        'title' => 'Попытка обновить чужую задачу',
    ], $this->headers);

    // Проверяем, что доступ запрещен
    $response->assertStatus(403);
});

test('пользователь не может удалить задачу другого пользователя', function () {
    // Создаем другого пользователя и его задачу
    $otherUser = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $otherUser->id]);

    // Выполняем запрос
    $response = $this->deleteJson("/api/tasks/{$task->id}", [], $this->headers);

    // Проверяем, что доступ запрещен
    $response->assertStatus(403);

    // Проверяем, что задача все еще существует
    $this->assertDatabaseHas('tasks', ['id' => $task->id]);
});

test('запросы API требуют аутентификации', function () {
    // Попытка получить список задач без аутентификации
    $this->getJson('/api/tasks')->assertStatus(401);

    // Попытка создать задачу без аутентификации
    $this->postJson('/api/tasks', [
        'title' => 'Test Task',
        'description' => 'Test Description',
        'due_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'status' => 'pending',
    ])->assertStatus(401);

    // Создаем задачу для тестирования
    $task = Task::factory()->create();

    // Попытка получить информацию без аутентификации
    $this->getJson("/api/tasks/{$task->id}")->assertStatus(401);

    // Попытка обновить задачу без аутентификации
    $this->putJson("/api/tasks/{$task->id}", [
        'title' => 'Updated Title',
    ])->assertStatus(401);

    // Попытка удалить задачу без аутентификации
    $this->deleteJson("/api/tasks/{$task->id}")->assertStatus(401);
});
