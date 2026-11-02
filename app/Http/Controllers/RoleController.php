<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::query()->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group'),
        ]);
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group'),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->isAdmin()) {
            return redirect()
                ->route('roles.index')
                ->withErrors(['role' => 'Peran Admin selalu memiliki akses penuh dan tidak dapat diubah.']);
        }

        $role->permissions()->sync($request->validated('permissions') ?? []);

        $this->activityLogger->record(
            'role.updated',
            $role,
            "Hak akses peran {$role->name} diperbarui.",
            ['permissions' => $role->permissions()->pluck('slug')->all()],
        );

        return redirect()
            ->route('roles.index')
            ->with('status', 'Hak akses peran berhasil diperbarui.');
    }
}
