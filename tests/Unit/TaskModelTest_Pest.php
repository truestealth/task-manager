<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('задача принадлежит пользователю', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    expect($task->user)->toBeInstanceOf(User::class)
        ->and($task->user->id)->toBe($this->user->id);
});

test('задача имеет правильные заполняемые поля', function () {
    $task = new Task();
    $fillable = ['title', 'description', 'due_date', 'status', 'user_id'];

    expect($task->getFillable())->toBe($fillable);
});

test('поле due_date преобразуется в объект Carbon', function () {
    $task = Task::factory()->create([
        'due_date' => now(),
    ]);

    expect($task->due_date)->toBeInstanceOf(Illuminate\Support\Carbon::class);
});

test('можно создать задачу', function () {
    $taskData = [
        'title' => 'Тестовая задача',
        'description' => 'Описание тестовой задачи',
        'due_date' => now()->addDay(),
        'status' => 'new',
        'user_id' => $this->user->id,
    ];

    $task = Task::create($taskData);

    expect($task)->toBeInstanceOf(Task::class)
        ->and($task->title)->toBe('Тестовая задача')
        ->and($task->user_id)->toBe($this->user->id);

    // Проверяем, что запись существует в базе
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Тестовая задача',
    ]);
});

test('можно обновить задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    $task->update([
        'title' => 'Обновленная задача',
        'status' => 'completed',
    ]);

    expect($task->title)->toBe('Обновленная задача')
        ->and($task->status)->toBe('completed');

    // Проверяем, что запись обновлена в базе
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Обновленная задача',
        'status' => 'completed',
    ]);
});

test('можно удалить задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);
    $taskId = $task->id;

    $task->delete();

    // Проверяем, что запись удалена из базы
    $this->assertDatabaseMissing('tasks', [
        'id' => $taskId,
    ]);
});

test('задача со статусом completed не считается просроченной', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'due_date' => now()->subDay(), // срок был вчера
        'status' => 'completed',
    ]);

    // Предполагаем, что у нас есть метод isOverdue() в модели Task
    expect($task->isOverdue())->toBeFalse();
});

test('задача со статусом отличным от completed и прошедшей датой считается просроченной', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'due_date' => now()->subDay(), // срок был вчера
        'status' => 'new',
    ]);

    // Предполагаем, что у нас есть метод isOverdue() в модели Task
    expect($task->isOverdue())->toBeTrue();
});

test('задача с будущей датой не считается просроченной', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'due_date' => now()->addDay(), // срок завтра
        'status' => 'new',
    ]);

    // Предполагаем, что у нас есть метод isOverdue() в модели Task
    expect($task->isOverdue())->toBeFalse();
});
