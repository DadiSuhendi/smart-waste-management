<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'device_name' => ['nullable', 'string', 'max:100']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
        }

        return $this->authenticate($request, $user);
    }

    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);
        $user = User::create(collect($data)->only(['name', 'email', 'password'])->all());

        return $this->authenticate($request, $user->refresh(), 201);
    }

    private function authenticate(Request $request, User $user, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->is('api/*')) {
            return response()->json(['user' => $user, 'token' => $user->createToken($request->input('device_name') ?: 'mobile', ['*'], now()->addDays(30))->plainTextToken, 'token_type' => 'Bearer'], $status);
        }
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->is('api/*')) {
            $request->user()->currentAccessToken()?->delete();

            return response()->json(['message' => 'Berhasil keluar.']);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
