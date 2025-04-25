# Laravel Task Management API

Это простое API-приложение для управления задачами, созданное с использованием Laravel 12. Оно предоставляет базовые функции CRUD для задач, аутентификацию пользователей с помощью Sanctum и фильтрацию задач.

## Основные функции

*   Регистрация и аутентификация пользователей (Laravel Sanctum).
*   Создание, чтение, обновление и удаление задач (CRUD).
*   Фильтрация задач по статусу и дате выполнения.
*   Авторизация на основе политик (Policy) для управления задачами.
*   Кэширование списка задач.
*   Настроен статический анализ (PHPStan), форматирование кода (Pint) и рефакторинг (Rector).
*   Используется PestPHP для тестирования.
*   Локальное окружение развертывается с помощью Laravel Sail (Docker).

## Требования

*   PHP >= 8.4
*   Composer
*   Docker (для использования Laravel Sail)
*   Node.js и npm (для frontend-зависимостей, если потребуются)

## Установка

1.  **Клонировать репозиторий:**
    ```bash
    git clone https://github.com/truestealth/task-manager.git
    cd task-manager
    ```

2.  **Скопировать файл окружения:**
    ```bash
    cp .env.example .env
    ```
    *Не забудьте настроить параметры базы данных (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) в `.env` файле.*

3.  **Установить зависимости Composer:**
    *   Если Sail еще не запущен:
        ```bash
        docker run --rm \
            -u "$(id -u):$(id -g)" \
            -v "$(pwd):/var/www/html" \
            -w /var/www/html \
            laravelsail/php84-composer:latest \
            composer install --ignore-platform-reqs
        ```
    *   Или после запуска Sail (см. шаг 5):
        ```bash
        ./vendor/bin/sail composer install
        ```

4.  **Сгенерировать ключ приложения:**
    ```bash
    ./vendor/bin/sail artisan key:generate
    ```

5.  **Запустить контейнеры Sail:**
    ```bash
    ./vendor/bin/sail up -d
    ```
    *(Ключ `-d` запускает контейнеры в фоновом режиме)*

6.  **Выполнить миграции базы данных:**
    ```bash
    ./vendor/bin/sail artisan migrate
    ```

7.  **(Опционально) Заполнить базу данных тестовыми данными:**
    ```bash
    ./vendor/bin/sail artisan db:seed
    ```

## Использование (API Эндпоинты)

Все эндпоинты имеют префикс `/api`. Для доступа к защищенным маршрутам требуется `Bearer` токен аутентификации в заголовке `Authorization`.

*   **Аутентификация**
    *   `POST /register` - Регистрация нового пользователя.
        *   Тело запроса: `{ "name": "John Doe", "email": "john@example.com", "password": "password", "password_confirmation": "password" }`
        *   Ответ: `{ "user": { ... }, "token": "..." }` (201 Created)
    *   `POST /login` - Вход пользователя.
        *   Тело запроса: `{ "email": "john@example.com", "password": "password" }`
        *   Ответ: `{ "user": { ... }, "token": "..." }` (200 OK)
    *   `POST /logout` (Требуется аутентификация) - Выход пользователя (удаление токенов).
        *   Ответ: `{ "message": "Успешный выход из системы" }` (200 OK)

*   **Задачи** (Требуется аутентификация)
    *   `GET /tasks` - Получение списка задач текущего пользователя.
        *   Параметры запроса (опционально):
            *   `status` (string): Фильтр по статусу (`pending`, `in_progress`, `completed`).
            *   `due_date` (string, YYYY-MM-DD): Фильтр по точной дате выполнения.
            *   `due_date_after` (string, YYYY-MM-DD): Фильтр по дате ПОСЛЕ указанной.
            *   `due_date_before` (string, YYYY-MM-DD): Фильтр по дате ДО указанной.
        *   Ответ: `[ { ...task... }, ... ]` (200 OK)
    *   `POST /tasks` - Создание новой задачи.
        *   Тело запроса: `{ "title": "Новая задача", "description": "Описание", "due_date": "YYYY-MM-DD", "status": "pending" }`
        *   Ответ: `{ ...task... }` (201 Created)
    *   `GET /tasks/{id}` - Получение информации о конкретной задаче.
        *   Ответ: `{ ...task... }` (200 OK)
    *   `PUT /tasks/{id}` - Обновление задачи.
        *   Тело запроса (можно передавать только изменяемые поля): `{ "title": "Обновленное название", "status": "completed" }`
        *   Ответ: `{ ...task... }` (200 OK)
    *   `DELETE /tasks/{id}` - Удаление задачи.
        *   Ответ: (пусто) (204 No Content)

## Структура тестов

Тесты расположены в директории `tests` и имеют суффикс `_Pest.php` для обозначения, что они написаны с использованием Pest PHP.

*   **Feature тесты**: Тестируют API эндпоинты и функциональность приложения.
    *   `tests/Feature/AuthTest_Pest.php` - Тесты аутентификации
    *   `tests/Feature/TaskApiTest_Pest.php` - Тесты API задач
    *   `tests/Feature/TaskCacheTest_Pest.php` - Тесты кэширования задач
    *   `tests/Feature/TaskFilteringTest_Pest.php` - Тесты фильтрации задач

*   **Unit тесты**: Тестируют отдельные компоненты приложения.
    *   `tests/Unit/TaskModelTest_Pest.php` - Тесты модели Task
    *   `tests/Unit/TaskPolicyTest_Pest.php` - Тесты политик доступа
    *   `tests/Unit/TaskValidationTest_Pest.php` - Тесты валидации задач

## Тестирование

Для запуска тестов используйте Sail:

*   **Запуск всех тестов (Pest):**
    ```bash
    ./vendor/bin/sail test
    ```
    Или:
    ```bash
    ./vendor/bin/sail pest
    ```

*   **Запуск полного набора проверок (Pest, Pint, PHPStan, Rector):**
    *   Найдите команду в `composer.json` в секции `scripts.test`. По умолчанию это может быть:
    ```bash
    ./vendor/bin/sail composer test
    ```

## Статический анализ и форматирование

*   **Запуск PHPStan:**
    ```bash
    ./vendor/bin/sail phpstan analyse
    ```

*   **Проверка форматирования Pint:**
    ```bash
    ./vendor/bin/sail pint --test
    ```

*   **Исправление форматирования Pint:**
    ```bash
    ./vendor/bin/sail pint
    ```
