<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->with('role')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('role_id'), fn ($query, $role) => $query->where('role_id', $role))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'filters' => $request->only(['search', 'role_id', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role_id' => $request->validated('role_id'),
            'password' => Hash::make($request->validated('password')),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->activityLogger->record(
            'user.created',
            $user,
            "Pengguna {$user->name} ditambahkan dengan peran {$user->role?->name}.",
        );

        return redirect()
            ->route('users.index')
            ->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user->load('role'),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($user->is($request->user()) && (int) $request->validated('role_id') !== (int) $user->role_id) {
            throw ValidationException::withMessages([
                'role_id' => 'Anda tidak dapat mengubah peran akun sendiri.',
            ]);
        }

        $isDemotingAdmin = $user->isAdmin() && (int) $request->validated('role_id') !== (int) $user->role_id;

        if ($isDemotingAdmin && $this->activeAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'role_id' => 'Peran admin terakhir tidak dapat diubah.',
            ]);
        }

        $payload = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role_id' => $request->validated('role_id'),
            'is_active' => $user->is($request->user()) ? true : $request->boolean('is_active'),
        ];

        if (filled($request->validated('password'))) {
            $payload['password'] = Hash::make($request->validated('password'));
        }

        $user->update($payload);

        $this->activityLogger->record(
            'user.updated',
            $user,
            "Pengguna {$user->name} diperbarui.",
            ['role_id' => $user->role_id, 'is_active' => $user->is_active],
        );

        return redirect()
            ->route('users.index')
            ->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(request()->user()?->hasPermission('user.update'), 403);

        if ($user->is(auth()->user())) {
            return back()->with('error', 'Akun sendiri tidak dapat dihapus.');
        }

        if ($user->role?->isAdmin()) {
            return back()->with('error', 'Akun admin sistem tidak dapat dihapus.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'Pengguna berhasil dihapus.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('user.deactivate'), 403);

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        if ($user->is_active && $user->isAdmin() && $this->activeAdminCount() <= 1) {
            return back()->withErrors(['user' => 'Admin terakhir tidak dapat dinonaktifkan.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $this->activityLogger->record(
            'user.status_changed',
            $user,
            "Pengguna {$user->name} ".($user->is_active ? 'diaktifkan' : 'dinonaktifkan').'.',
        );

        return back()->with('status', 'Status pengguna berhasil diperbarui.');
    }

    private function activeAdminCount(): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', Role::ADMIN_SLUG))
            ->count();
    }
}
