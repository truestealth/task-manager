<?php

declare(strict_types=1);

use App\Http\Resources\TaskCollectionResource;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

describe('TaskCollection', function (): void {
    it('возвращает коллекцию задач с метаданными', function (): void {
        $tasks = Task::factory()->count(2)->create();
        $resource = new TaskCollectionResource($tasks);
        $request = Request::create('/api/tasks', 'GET');
        $array = $resource->toArray($request);
        expect($array)->toHaveKey('data')
            ->and($array)->toHaveKey('meta')
            ->and($array['meta'])->toBe(['total' => 2]);
        foreach ($array['data'] as $item) {
            expect($item)->toBeArray()
                ->and($item)->toHaveKey('id')
                ->and($item)->toHaveKey('title')
                ->and($item)->toHaveKey('description')
                ->and($item)->toHaveKey('due_date')
                ->and($item)->toHaveKey('status')
                ->and($item)->toHaveKey('created_at')
                ->and($item)->toHaveKey('updated_at')
                ->and($item)->toHaveKey('user');
            expect($item['user'])->toBeArray()
                ->and($item['user'])->toHaveKey('id')
                ->and($item['user'])->toHaveKey('name');
        }
    });
});
