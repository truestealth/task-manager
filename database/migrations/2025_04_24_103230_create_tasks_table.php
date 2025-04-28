<?php

declare(strict_types=1);

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
        Schema::create('tasks', function (Blueprint $table): void {
            // Основные поля
            $table->id();
            $table->string('title'); // Название задачи
            $table->text('description'); // Подробное описание задачи
            $table->dateTime('due_date'); // Срок выполнения задачи
            $table->enum('status', ['new', 'in_progress', 'completed'])->default('new'); // Статус задачи
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Связь с пользователем
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('due_date');

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
