<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        // $this->authorizeResource(Role::class, 'role'); // Jika Anda membuat RolePolicy
    }

    public function index(Request $request)
    {
        if (!$request->user()->can('manage roles')) { 
            abort(403);
        }

        $query = Role::orderBy('name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $roles = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles->through(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name')->toArray(), 
                    'permissions_string' => $role->permissions->pluck('name')->implode(', '),
                ];
            }),
            'filters' => $request->only(['search']),
            'allPermissions' => Permission::orderBy('name')->pluck('name'), 
            'can' => [
                'create_role' => $request->user()->can('manage roles'),
                'edit_role' => $request->user()->can('manage roles'),
                'delete_role' => $request->user()->can('manage roles'),
            ]
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web', 
        ]);

        if (!empty($validated['permissions'])) {
            $role->givePermissionTo($validated['permissions']);
        }

        return Redirect::route('admin.roles.index')->with([
            'message' => 'Role created successfully.',
            'type' => 'success'
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();

        $role->name = $validated['name'];
        $role->guard_name = $validated['guard_name'] ?? $role->guard_name; 
        $role->save();

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        } else {
            $role->syncPermissions([]); 
        }

        return Redirect::route('admin.roles.index')->with([
            'message' => 'Role updated successfully.',
            'type' => 'success'
        ]);
    }

    public function destroy(Request $request, Role $role)
    {
        if (!$request->user()->can('manage roles')) {
            abort(403);
        }

        if (in_array($role->name, ['admin', 'super-admin'])) {
            return Redirect::route('admin.roles.index')->with([
                'message' => 'Cannot delete essential roles.',
                'type' => 'error'
            ]);
        }

        $role->delete();

        return Redirect::route('admin.roles.index')->with([
            'message' => 'Role deleted successfully.',
            'type' => 'success'
        ]);
    }
}