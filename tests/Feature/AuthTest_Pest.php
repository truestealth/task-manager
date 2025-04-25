<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

test('пользователь может зарегистрироваться', function () {
    $userData = [
        'name' => 'Тестовый пользователь',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(201)
        ->assertJson(fn (AssertableJson $json) => $json->has('user', fn ($json) => $json->where('name', $userData['name'])
            ->where('email', $userData['email'])
            ->has('id')
            ->has('created_at')
            ->has('updated_at')
        )
            ->has('token')
        );

    $this->assertDatabaseHas('users', [
        'name' => $userData['name'],
        'email' => $userData['email'],
    ]);
});

test('нельзя зарегистрироваться с уже существующим email', function () {
    // Создаем пользователя
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    // Пытаемся зарегистрировать пользователя с тем же email
    $userData = [
        'name' => 'Другой пользователь',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('пользователь может авторизоваться', function () {
    // Создаем пользователя
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('password123'),
    ]);

    // Авторизуемся
    $response = $this->postJson('/api/login', [
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has('user', fn ($json) => $json->where('id', $user->id)
            ->where('name', $user->name)
            ->where('email', $user->email)
        )
            ->has('token')
        );
});

test('нельзя авторизоваться с неверными учетными данными', function () {
    // Создаем пользователя
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    // Пытаемся авторизоваться с неверным паролем
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(401)
        ->assertJsonValidationErrors(['email']);
});

test('авторизованный пользователь может получить информацию о своем профиле', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->where('id', $user->id)
            ->where('name', $user->name)
            ->where('email', $user->email)
        );
});

test('пользователь может выйти из системы', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Выход выполнен успешно',
        ]);

    // Проверяем, что токен удален из базы данных
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('неавторизованный пользователь не может получить доступ к защищенным маршрутам', function () {
    // Попытка получить информацию о профиле без токена
    $this->getJson('/api/user')->assertStatus(401);

    // Попытка выйти из системы без токена
    $this->postJson('/api/logout')->assertStatus(401);
});

test('токен действителен и может использоваться для запросов', function () {
    // Регистрируем пользователя и получаем токен
    $userData = [
        'name' => 'Тестовый пользователь для токена',
        'email' => 'token@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $registerResponse = $this->postJson('/api/register', $userData);
    $token = $registerResponse->json('token');

    // Используем полученный токен для запроса информации о пользователе
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJson([
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
});

test('регистрация требует подтверждения пароля', function () {
    $userData = [
        'name' => 'Тестовый пользователь',
        'email' => 'test@example.com',
        'password' => 'password123',
        // Отсутствует password_confirmation
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('email должен быть валидным', function () {
    $userData = [
        'name' => 'Тестовый пользователь',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('пароль должен быть не менее 8 символов', function () {
    $userData = [
        'name' => 'Тестовый пользователь',
        'email' => 'test@example.com',
        'password' => '123',
        'password_confirmation' => '123',
    ];

    $response = $this->postJson('/api/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
