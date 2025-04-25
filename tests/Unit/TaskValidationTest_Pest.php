<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('auth_token')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

test('создание задачи требует заполнения всех обязательных полей', function () {
    // Отправляем запрос с пустыми данными
    $response = $this->postJson('/api/tasks', [], $this->headers);

    // Проверяем, что сервер вернул ошибки валидации
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'due_date', 'status']);
});

test('заголовок задачи не может быть длиннее 255 символов', function () {
    // Создаем слишком длинный заголовок
    $longTitle = str_repeat('a', 256);

    $response = $this->postJson('/api/tasks', [
        'title' => $longTitle,
        'description' => 'Описание',
        'due_date' => now()->format('Y-m-d'),
        'status' => 'new',
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('статус задачи должен быть одним из допустимых значений', function () {
    // Отправляем запрос с неверным статусом
    $response = $this->postJson('/api/tasks', [
        'title' => 'Тестовая задача',
        'description' => 'Описание',
        'due_date' => now()->format('Y-m-d'),
        'status' => 'invalid_status',
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    // Проверяем, что все допустимые статусы проходят валидацию
    foreach (['new', 'pending', 'completed'] as $validStatus) {
        $this->postJson('/api/tasks', [
            'title' => 'Тестовая задача',
            'description' => 'Описание',
            'due_date' => now()->format('Y-m-d'),
            'status' => $validStatus,
        ], $this->headers)->assertStatus(201);
    }
});

test('дата выполнения должна быть в правильном формате', function () {
    // Неверный формат даты
    $response = $this->postJson('/api/tasks', [
        'title' => 'Тестовая задача',
        'description' => 'Описание',
        'due_date' => 'не-дата',
        'status' => 'new',
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['due_date']);

    // Правильные форматы даты
    foreach ([now()->format('Y-m-d'), now()->format('Y-m-d H:i:s')] as $validDate) {
        $this->postJson('/api/tasks', [
            'title' => 'Тестовая задача '.rand(1, 1000),
            'description' => 'Описание',
            'due_date' => $validDate,
            'status' => 'new',
        ], $this->headers)->assertStatus(201);
    }
});

test('можно обновить только отдельные поля задачи', function () {
    // Создаем задачу
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Исходная задача',
        'status' => 'new',
    ]);

    // Обновляем только заголовок
    $response = $this->putJson("/api/tasks/{$task->id}", [
        'title' => 'Обновленный заголовок',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJson([
            'title' => 'Обновленный заголовок',
            'status' => 'new', // Статус не изменился
        ]);

    // Обновляем только статус
    $response = $this->putJson("/api/tasks/{$task->id}", [
        'status' => 'completed',
    ], $this->headers);

    $response->assertStatus(200)
        ->assertJson([
            'title' => 'Обновленный заголовок', // Заголовок не изменился
            'status' => 'completed',
        ]);
});

test('при обновлении задачи также проверяется валидация', function () {
    // Создаем задачу
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Исходная задача',
        'status' => 'new',
    ]);

    // Пытаемся обновить с неверным статусом
    $response = $this->putJson("/api/tasks/{$task->id}", [
        'status' => 'invalid_status',
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    // Пытаемся обновить с слишком длинным заголовком
    $response = $this->putJson("/api/tasks/{$task->id}", [
        'title' => str_repeat('a', 256),
    ], $this->headers);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('валидация работает одинаково для создания и обновления задач', function () {
    // Проверяем валидацию для создания
    $validationErrors = $this->postJson('/api/tasks', [
        'title' => str_repeat('a', 256),
        'description' => 'Описание',
        'due_date' => 'не-дата',
        'status' => 'invalid_status',
    ], $this->headers)->assertStatus(422)->json('errors');

    // Создаем задачу для тестирования обновления
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    // Проверяем валидацию для обновления
    $updateErrors = $this->putJson("/api/tasks/{$task->id}", [
        'title' => str_repeat('a', 256),
        'description' => 'Описание',
        'due_date' => 'не-дата',
        'status' => 'invalid_status',
    ], $this->headers)->assertStatus(422)->json('errors');

    // Проверяем, что ошибки для одних и тех же полей совпадают
    foreach (['title', 'due_date', 'status'] as $field) {
        expect(isset($validationErrors[$field]))->toBeTrue()
            ->and(isset($updateErrors[$field]))->toBeTrue();
    }
});
