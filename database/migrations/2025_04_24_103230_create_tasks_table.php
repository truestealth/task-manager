<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            // Основные поля
            $table->id();
            $table->string('title'); // Название задачи
            $table->text('description'); // Подробное описание задачи
            $table->dateTime('due_date'); // Срок выполнения задачи
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending'); // Статус задачи
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Связь с пользователем
            $table->timestamps();
            
            // Составной индекс для оптимизации запросов по статусу и сроку выполнения
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
