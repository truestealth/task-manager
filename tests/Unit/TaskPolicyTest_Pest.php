<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->policy = new TaskPolicy();
});

test('пользователь может просматривать свою задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    expect($this->policy->view($this->user, $task))->toBeTrue();
});

test('пользователь не может просматривать чужую задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->otherUser->id]);

    expect($this->policy->view($this->user, $task))->toBeFalse();
});

test('пользователь может обновлять свою задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    expect($this->policy->update($this->user, $task))->toBeTrue();
});

test('пользователь не может обновлять чужую задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->otherUser->id]);

    expect($this->policy->update($this->user, $task))->toBeFalse();
});

test('пользователь может удалять свою задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    expect($this->policy->delete($this->user, $task))->toBeTrue();
});

test('пользователь не может удалять чужую задачу', function () {
    $task = Task::factory()->create(['user_id' => $this->otherUser->id]);

    expect($this->policy->delete($this->user, $task))->toBeFalse();
});

test('пользователь может просматривать, обновлять и удалять все свои задачи', function () {
    $tasks = Task::factory()->count(3)->create(['user_id' => $this->user->id]);

    foreach ($tasks as $task) {
        expect($this->policy->view($this->user, $task))->toBeTrue()
            ->and($this->policy->update($this->user, $task))->toBeTrue()
            ->and($this->policy->delete($this->user, $task))->toBeTrue();
    }
});

test('пользователь не может просматривать, обновлять и удалять ни одну из чужих задач', function () {
    $tasks = Task::factory()->count(3)->create(['user_id' => $this->otherUser->id]);

    foreach ($tasks as $task) {
        expect($this->policy->view($this->user, $task))->toBeFalse()
            ->and($this->policy->update($this->user, $task))->toBeFalse()
            ->and($this->policy->delete($this->user, $task))->toBeFalse();
    }
});
