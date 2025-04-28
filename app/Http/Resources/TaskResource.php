<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Task;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TaskResource",
 *     type="object",
 *     required={"id", "title", "due_date", "status", "user", "created_at", "updated_at"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Test task"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Test description"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2024-04-25"),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-04-25 21:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-25 21:00:00")
 * )
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property \Carbon\Carbon|string $due_date
 * @property string $status
 * @property array{id:int, name:string}|null $user
 * @property \Carbon\Carbon|string $created_at
 * @property \Carbon\Carbon|string $updated_at
 */
final class TaskResource extends JsonResource
{
    /**
     * @return array{id:int,title:string,description:?string,due_date:string,status:string,user:array{id:int,name:string}|null,created_at:string,updated_at:string}
     */
    public function toArray(Request $request): array
    {
        /** @var Task $task */
        $task = $this->resource;
        /** @var User|null $user */
        $user = $task->user;
        /** @var DateTimeInterface $dueDate */
        $dueDate = $task->due_date;
        /** @var DateTimeInterface $createdAt */
        $createdAt = $task->created_at;
        /** @var DateTimeInterface $updatedAt */
        $updatedAt = $task->updated_at;

        return [
            'id' => (int) $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $dueDate->format('Y-m-d'),
            'status' => $task->status,
            'user' => $user
                ? [
                    'id' => (int) $user->id,
                    'name' => (string) $user->name,
                ]
                : null,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
