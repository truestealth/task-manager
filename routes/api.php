<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;

/**
 * Маршруты аутентификации
 */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/**
 * Группа маршрутов, защищенных аутентификацией Sanctum
 */
Route::middleware('auth:sanctum')->group(function () {
    // Маршрут выхода из системы
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ресурсные маршруты для управления задачами
    // GET /api/tasks - получение списка задач
    // POST /api/tasks - создание новой задачи
    // GET /api/tasks/{id} - получение информации о задаче
    // PUT /api/tasks/{id} - обновление задачи
    // DELETE /api/tasks/{id} - удаление задачи
    Route::apiResource('tasks', TaskController::class);
}); 