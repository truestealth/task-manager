<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
final class TaskFactory extends Factory
{
    /**
     * Модель, для которой предназначена фабрика.
     */
    protected $model = Task::class;

    /**
     * Определение состояния модели по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 weeks')->format('Y-m-d H:i:s'),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Задача со статусом "pending".
     */
    public function pending(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
        ]);
    }

    /**
     * Задача со статусом "in_progress".
     */
    public function inProgress(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Задача со статусом "completed".
     */
    public function completed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
        ]);
    }

    /**
     * Просроченная задача.
     */
    public function overdue(): self
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => $this->faker->dateTimeBetween('-1 month', 'yesterday')->format('Y-m-d H:i:s'),
            'status' => $this->faker->randomElement(['pending', 'in_progress']),
        ]);
    }

    /**
     * Задача на сегодня.
     */
    public function dueToday(): self
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
