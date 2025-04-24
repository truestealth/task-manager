<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово назначать
     */
    protected $fillable = [
        'title', // Название задачи
        'description', // Описание задачи
        'due_date', // Срок выполнения
        'status', // Статус задачи
        'user_id' // ID пользователя
    ];

    /**
     * Преобразование типов данных
     */
    protected $casts = [
        'due_date' => 'datetime', // Преобразование даты в объект DateTime
    ];

    /**
     * Связь с моделью User
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
