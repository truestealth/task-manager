<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AuthController', function (): void {
    it('регистрирует нового пользователя', function (): void {
        $data = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $response = $this->postJson('/api/register', $data);
        $response->assertCreated()->assertJsonStructure(['user', 'token']);
        $this->assertDatabaseHas('users', ['email' => 'testuser@example.com']);
    });

    it('логинит существующего пользователя', function (): void {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $data = [
            'email' => $user->email,
            'password' => 'password',
        ];
        $response = $this->postJson('/api/login', $data);
        $response->assertOk()->assertJsonStructure(['token']);
    });

    it('выход из системы', function (): void {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login->json('token');
        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $response->assertOk()->assertJsonPath('message', 'Successfully logged out');
    });

    it('отклоняет неверный пароль', function (): void {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $data = [
            'email' => $user->email,
            'password' => 'wrong-password',
        ];
        $response = $this->postJson('/api/login', $data);
        $response->assertStatus(401);
    });

    it('требует авторизацию для получения профиля', function (): void {
        $this->getJson('/api/user')->assertUnauthorized();
    });

    it('возвращает профиль аутентифицированного пользователя', function (): void {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $login = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        $token = $login->json('token');
        $response = $this->getJson('/api/user', ['Authorization' => 'Bearer '.$token]);
        $response->assertOk()->assertJsonPath('id', $user->id)->assertJsonPath('email', $user->email);
    });

    it('требует авторизацию для обновления профиля', function (): void {
        $this->putJson('/api/user', ['name' => 'X'])->assertUnauthorized();
    });

    it('обновляет профиль пользователя', function (): void {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $login = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
        $token = $login->json('token');
        $response = $this->putJson('/api/user', ['name' => 'New Name', 'email' => 'new@example.com'], ['Authorization' => 'Bearer '.$token]);
        $response->assertOk()->assertJsonPath('name', 'New Name')->assertJsonPath('email', 'new@example.com');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'new@example.com']);
    });
});
