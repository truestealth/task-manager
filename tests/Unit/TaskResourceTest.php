<?php

declare(strict_types=1);

use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TaskResource', function (): void {
    it('преобразует задачу в массив', function (): void {
        $task = Task::factory()->create(['title' => 'Test', 'description' => 'Desc', 'status' => 'new']);
        $resource = TaskResource::make($task->load('user'))->resolve();
        expect($resource)
            ->toHaveKey('id', $task->id)
            ->toHaveKey('title', 'Test')
            ->toHaveKey('description', 'Desc')
            ->toHaveKey('status', 'new')
            ->toHaveKey('created_at')
            ->toHaveKey('updated_at')
            ->toHaveKey('due_date')
            ->toHaveKey('user');
        expect($resource['user'])->toBeArray()
            ->toHaveKey('id', $task->user->id)
            ->toHaveKey('name', $task->user->name);
    });
});
