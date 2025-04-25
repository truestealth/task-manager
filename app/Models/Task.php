<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property \Illuminate\Support\Carbon $due_date
 * @property string $status
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
final class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * Атрибуты, для которых разрешено массовое присвоение.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title', // Название задачи
        'description', // Описание задачи
        'due_date', // Срок выполнения
        'status', // Статус задачи
        'user_id', // ID пользователя
    ];

    /**
     * Атрибуты, которые должны быть преобразованы к нативным типам.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'datetime', // Преобразование даты в объект DateTime
    ];

    /**
     * Получить пользователя, которому принадлежит задача.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
