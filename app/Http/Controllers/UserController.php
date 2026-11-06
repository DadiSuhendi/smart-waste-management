<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $users = User::withCount('reports')->when($request->input('q'), fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))->latest('id')->paginate(10)->withQueryString();

        return $request->is('api/*') ? response()->json($users) : view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User]);
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        return $this->save($request, new User);
    }

    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        return $this->save($request, $user);
    }

    private function save(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $new = ! $user->exists;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(['admin', 'user'])], 'password' => [$new ? 'required' : 'nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            throw ValidationException::withMessages(['role' => 'Anda tidak dapat menurunkan role akun sendiri.']);
        }
        $user->fill(collect($data)->only(['name', 'email'])->all());
        $user->role = $data['role'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        if (! $new && ! empty($data['password'])) {
            $user->tokens()->delete();
        }

        return $request->is('api/*') ? response()->json(['data' => $user], $new ? 201 : 200) : redirect()->route('users.index')->with('success', 'Pengguna berhasil disimpan.');
    }

    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if ($user->id === $request->user()->id || $user->reports()->exists()) {
            throw ValidationException::withMessages(['user' => 'Akun sendiri atau pengguna yang memiliki laporan tidak dapat dihapus.']);
        }
        $user->tokens()->delete();
        $user->delete();

        return $request->is('api/*') ? response()->json(['message' => 'Pengguna dihapus.']) : back()->with('success', 'Pengguna dihapus.');
    }
}
