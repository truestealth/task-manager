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

test('задачи можно фильтровать по статусу', function () {
    // Создаем задачи с разными статусами
    Task::factory()->pending()->create([
        'user_id' => $this->user->id,
        'title' => 'Задача в процессе',
    ]);

    Task::factory()->completed()->create([
        'user_id' => $this->user->id,
        'title' => 'Завершенная задача',
    ]);

    // Фильтрация по статусу "pending"
    $this->getJson('/api/tasks?status=pending', $this->headers)
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJson(fn (AssertableJson $json) => $json->has(0, fn ($json) => $json->where('title', 'Задача в процессе')
            ->where('status', 'pending')
            ->etc()
        )
        );

    // Фильтрация по статусу "completed"
    $this->getJson('/api/tasks?status=completed', $this->headers)
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJson(fn (AssertableJson $json) => $json->has(0, fn ($json) => $json->where('title', 'Завершенная задача')
            ->where('status', 'completed')
            ->etc()
        )
        );
});

test('задачи можно фильтровать по дате выполнения', function () {
    // Задача на сегодня
    Task::factory()->dueToday()->create([
        'user_id' => $this->user->id,
        'title' => 'Задача на сегодня',
    ]);

    // Задача на завтра
    Task::factory()->create([
        'user_id' => $this->user->id,
        'due_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'title' => 'Задача на завтра',
    ]);

    // Просроченная задача
    Task::factory()->overdue()->create([
        'user_id' => $this->user->id,
        'title' => 'Просроченная задача',
    ]);

    // Фильтрация по конкретной дате (сегодня)
    $this->getJson('/api/tasks?due_date='.now()->format('Y-m-d'), $this->headers)
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJson(fn (AssertableJson $json) => $json->has(0, fn ($json) => $json->where('title', 'Задача на сегодня')
            ->etc()
        )
        );
});

test('задачи можно фильтровать по комбинации статуса и даты', function () {
    // Задача в процессе на сегодня
    Task::factory()->pending()->dueToday()->create([
        'user_id' => $this->user->id,
        'title' => 'Задача в процессе на сегодня',
    ]);

    // Завершенная задача на сегодня
    Task::factory()->completed()->dueToday()->create([
        'user_id' => $this->user->id,
        'title' => 'Завершенная задача на сегодня',
    ]);

    // Задача в процессе на завтра
    Task::factory()->pending()->create([
        'user_id' => $this->user->id,
        'due_date' => now()->addDay()->format('Y-m-d H:i:s'),
        'title' => 'Задача в процессе на завтра',
    ]);

    // Фильтрация по статусу "pending" и дате "сегодня"
    $this->getJson('/api/tasks?status=pending&due_date='.now()->format('Y-m-d'), $this->headers)
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJson(fn (AssertableJson $json) => $json->has(0, fn ($json) => $json->where('title', 'Задача в процессе на сегодня')
            ->where('status', 'pending')
            ->etc()
        )
        );

    // Фильтрация по статусу "completed" и дате "сегодня"
    $this->getJson('/api/tasks?status=completed&due_date='.now()->format('Y-m-d'), $this->headers)
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJson(fn (AssertableJson $json) => $json->has(0, fn ($json) => $json->where('title', 'Завершенная задача на сегодня')
            ->where('status', 'completed')
            ->etc()
        )
        );
});

test('фильтрация возвращает пустой массив, если нет совпадений', function () {
    // Создаем задачу с определенными параметрами
    Task::factory()->pending()->create([
        'user_id' => $this->user->id,
        'due_date' => now()->format('Y-m-d H:i:s'),
    ]);

    // Запрашиваем с параметрами, которым не соответствует ни одна задача
    $this->getJson('/api/tasks?status=completed&due_date='.now()->addWeek()->format('Y-m-d'), $this->headers)
        ->assertStatus(200)
        ->assertJsonCount(0)
        ->assertJson([]);
});

test('фильтрация возвращает только задачи текущего пользователя', function () {
    // Создаем задачи для текущего пользователя
    Task::factory()->pending()->create([
        'user_id' => $this->user->id,
        'title' => 'Задача текущего пользователя',
    ]);

    // Создаем другого пользователя и задачу для него
    $otherUser = User::factory()->create();
    Task::factory()->pending()->create([
        'user_id' => $otherUser->id,
        'title' => 'Задача другого пользователя',
    ]);

    // Проверяем, что фильтрация возвращает только задачи текущего пользователя
    $this->getJson('/api/tasks?status=pending', $this->headers)
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJson(fn (AssertableJson $json) => $json->has(0, fn ($json) => $json->where('title', 'Задача текущего пользователя')
            ->where('user_id', $this->user->id)
            ->etc()
        )
        );
});
