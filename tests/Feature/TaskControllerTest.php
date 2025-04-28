<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TaskController', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create(['password' => bcrypt('password')]);
        $login = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        $this->token = $login->json('token');
    });
    it('фильтрует задачи по статусу', function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'new']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'completed']);
        $response = $this->getJson('/api/tasks?status=new', [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertOk()->assertJsonCount(1, 'data');
        expect($response['data'][0]['status'])->toBe('new');
    });

    it('возвращает ошибку при невалидных данных', function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
        $response = $this->postJson('/api/tasks', ['title' => ''], [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    });

    it('возвращает 404 при попытке получить несуществующую задачу', function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
        $response = $this->getJson('/api/tasks/999999', [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertNotFound();
    });

    it('возвращает конкретную задачу', function (): void {
        Task::factory()->create(['user_id' => $this->user->id]);
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $response = $this->getJson("/api/tasks/{$task->id}", [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.user.id', $this->user->id)
            ->assertJsonPath('data.user.name', $this->user->name);
    });

    it('возвращает 401 для неавторизованного пользователя', function (): void {
        $response = $this->getJson('/api/tasks');
        $response->assertUnauthorized();
    });

    it('работает пагинация', function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
        Task::factory()->count(25)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/tasks?per_page=10', [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertOk()->assertJsonStructure(['data', 'meta']);
        expect($response['meta']['total'])->toBeGreaterThan(10);
    });
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    });

    it('возвращает список задач пользователя', function (): void {
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);
        $response = $this->getJson('/api/tasks', [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertOk()->assertJsonStructure(['data']);
    });

    it('создаёт новую задачу', function (): void {
        $data = [
            'title' => 'Test task',
            'description' => 'desc',
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'new',
        ];
        $response = $this->postJson('/api/tasks', $data, [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertCreated()->assertJsonPath('title', 'Test task');
    });

    it('обновляет задачу', function (): void {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $response = $this->putJson("/api/tasks/{$task->id}", ['title' => 'Updated'], [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertOk()->assertJsonPath('data.title', 'Updated');
    });

    it('удаляет задачу', function (): void {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $response = $this->deleteJson("/api/tasks/{$task->id}", [], [
            'Authorization' => 'Bearer '.$this->token,
        ]);
        $response->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    });

    it('не даёт доступ к чужой задаче', function (): void {
        $other = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $other->id]);
        $this->getJson("/api/tasks/{$task->id}", [
            'Authorization' => 'Bearer '.$this->token,
        ])->assertForbidden();
        $this->putJson("/api/tasks/{$task->id}", ['title' => 'X'], [
            'Authorization' => 'Bearer '.$this->token,
        ])->assertForbidden();
        $this->deleteJson("/api/tasks/{$task->id}", [], [
            'Authorization' => 'Bearer '.$this->token,
        ])->assertForbidden();
    });
});
