# Laravel Task Management API

Простое RESTful API для управления задачами, реализованное на Laravel 12.

**ВАЖНО**: Подробное описание задачи находится в файле [task.md](task.md)

## Возможности

- Регистрация и аутентификация пользователей (JWT).
- CRUD для задач (создание, получение, обновление, удаление).
- Фильтрация задач по статусу и сроку выполнения.
- Кэширование списка задач (Redis).
- Логирование каждого API-запроса через middleware.
- Swagger документация (l5-swagger).
- Postman коллекция для быстрого тестирования.
- Тесты (PestPHP) и статический анализ (PHPStan, Pint).

## Требования

- Docker (Laravel Sail)
- PHP >= 8.4
- Composer
- Node.js и npm (если нужны фронтенд-зависимости)
- Redis (как сервис или контейнер)

## Установка и запуск

1. Клонируйте репозиторий:
   ```bash
   git clone https://github.com/truestealth/task-manager.git
   cd task-manager
   ```
2. Скопируйте `.env.sail` в `.env`:
   ```bash
   cp .env.sail .env
   ```
3. Установите зависимости:
   ```bash
   composer install
   ```
4. Запустите Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
5. Сгенерируйте ключ приложения и JWT-секрет:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan jwt:secret
   ```
6. Выполните миграции и (опционально) сиды:
   ```bash
   ./vendor/bin/sail artisan migrate
   ./vendor/bin/sail artisan db:seed
   ```

## API документация

### Swagger UI
После генерации командой:
```bash
./vendor/bin/sail artisan l5-swagger:generate
```
документация доступна по адресу: `http://localhost/api/documentation`

### Postman
Импортируйте коллекцию `task_api_postman_collection.json` из корня проекта. Не забудьте задать переменные окружения:
- `baseUrl`: `http://localhost`
- `authToken`: (Bearer-токен после логина)

## Логирование API-запросов

Middleware логирует все входящие запросы в файл:
```
storage/logs/api-YYYY-MM-DD.log
```
Каждая запись содержит метод, URI, `user_id`, статус, длительность и IP.

## Эндпоинты

| Метод | URI             | Описание                      |
|-------|-----------------|-------------------------------|
| POST  | /api/register   | Регистрация пользователя      |
| POST  | /api/login      | Аутентификация                |
| POST  | /api/logout     | Выход                         |
| GET   | /api/user       | Профиль текущего пользователя |
| GET   | /api/tasks      | Список задач                  |
| POST  | /api/tasks      | Создание задачи               |
| GET   | /api/tasks/{id} | Получение задачи по ID        |
| PUT   | /api/tasks/{id} | Обновление задачи             |
| DELETE| /api/tasks/{id} | Удаление задачи               |

## Тестирование

Запуск тестов:
```bash
./vendor/bin/sail test
```
Полный набор проверок:
```bash
./vendor/bin/sail composer test
```

## Статический анализ и форматирование

- PHPStan: `./vendor/bin/sail phpstan analyse`
- Pint: `./vendor/bin/sail pint --test`; исправление: `./vendor/bin/sail pint`

## Лицензия

MIT
