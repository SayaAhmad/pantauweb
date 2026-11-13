<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->orderByDesc('is_admin')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::query()->create($this->validated($request));

        return redirect()
            ->route('users.index')
            ->with('status', "Pengguna \"{$user->name}\" ditambahkan.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && ! $data['is_admin']) {
            return back()
                ->withInput()
                ->withErrors(['is_admin' => 'Anda tidak bisa mencabut akses admin dari akun sendiri.']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('status', "Pengguna \"{$user->name}\" diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()
                ->route('users.index')
                ->withErrors(['user' => 'Anda tidak bisa menghapus akun sendiri.']);
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', "Pengguna \"{$name}\" dihapus.");
    }

    /**
     * @return array{name: string, email: string, is_admin: bool, password?: string}
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                Password::min(12),
                'confirmed',
            ],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $data['is_admin'] = $request->boolean('is_admin');

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
