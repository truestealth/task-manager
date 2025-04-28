<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="TaskCollectionResource",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/TaskResource")
 *     ),
 *
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="total", type="integer")
 *     )
 * )
 */
final class TaskCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array{
     *     data: mixed,
     *     meta: array{total: int}
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn (mixed $item): array => (new TaskResource($item))->toArray($request))->all(),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
