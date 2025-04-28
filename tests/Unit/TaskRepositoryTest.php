<?php

declare(strict_types=1);

use App\Http\Repositories\TaskRepository;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);

describe('TaskRepository', function (): void {
    beforeEach(function (): void {
        // Создаем пользователя и задачи для тестов
        $this->user = App\Models\User::factory()->create();
        $this->repository = app(TaskRepository::class);
    });

    it('возвращает задачи пользователя с фильтрами', function (): void {
        Task::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => 'new']);
        Task::factory()->count(1)->create(['user_id' => $this->user->id, 'status' => 'completed']);
        Task::factory()->count(1)->create(); // чужая задача

        $filters = ['status' => 'new'];
        $result = $this->repository->getFilteredTasks($this->user->id, $filters, 10);

        expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($result->total())->toBe(2);
    });

    it('создаёт новую задачу', function (): void {
        $data = [
            'title' => 'Test task',
            'description' => 'desc',
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'new',
            'user_id' => $this->user->id,
        ];
        $task = $this->repository->create($data);
        expect($task)->toBeInstanceOf(Task::class)
            ->and($task->title)->toBe('Test task');
    });

    it('обновляет задачу', function (): void {
        $task = Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Old']);
        $updated = $this->repository->update($task, ['title' => 'New']);
        expect($updated->title)->toBe('New');
    });

    it('удаляет задачу и чистит кэш', function (): void {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $result = $this->repository->delete($task);
        expect($result)->toBeTrue()
            ->and(Task::find($task->id))->toBeNull();
    });

});
