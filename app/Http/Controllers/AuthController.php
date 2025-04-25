<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    /**
     * Регистрация нового пользователя
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var string $name */
        $name = $validated['name'];
        /** @var string $email */
        $email = $validated['email'];
        /** @var string $password */
        $password = $validated['password'];

        // Создание пользователя
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Создание токена для нового пользователя
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Аутентификация пользователя
     *
     * @throws ValidationException
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        /** @var string $email */
        $email = $credentials['email'];
        /** @var string $password */
        $password = $credentials['password'];

        // Поиск пользователя по email
        $user = User::where('email', $email)->first();

        // Проверка пароля
        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверные учетные данные.'],
            ]);
        }

        // Создание нового токена
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Выход пользователя из системы
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Успешный выход из системы',
        ]);
    }
}
