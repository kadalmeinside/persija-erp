<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;  
use App\Http\Requests\Admin\UpdatePermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission; 

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->can('manage permissions')) {
            abort(403);
        }

        $query = Permission::orderBy('name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $permissions = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions->through(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                ];
            }),
            'filters' => $request->only(['search']),
            'can' => [
                'create_permission' => $request->user()->can('manage permissions'),
                'edit_permission' => $request->user()->can('manage permissions'),
                'delete_permission' => $request->user()->can('manage permissions'),
            ]
        ]);
    }

    public function store(StorePermissionRequest $request)
    {
        $validated = $request->validated();
        Permission::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);

        return Redirect::route('admin.permissions.index')->with([
            'message' => 'Permission created successfully.',
            'type' => 'success'
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $validated = $request->validated();
        $permission->name = $validated['name'];
        $permission->guard_name = $validated['guard_name'] ?? $permission->guard_name;
        $permission->save();

        return Redirect::route('admin.permissions.index')->with([
            'message' => 'Permission updated successfully.',
            'type' => 'success'
        ]);
    }

    public function destroy(Request $request, Permission $permission)
    {
        if (!$request->user()->can('manage permissions')) {
            abort(403);
        }

        
        if ($permission->roles()->count() > 0) {
            return Redirect::route('admin.permissions.index')->with([
                'message' => 'Cannot delete permission that is assigned to roles.',
                'type' => 'error'
            ]);
        }

        $permission->delete();

        return Redirect::route('admin.permissions.index')->with([
            'message' => 'Permission deleted successfully.',
            'type' => 'success'
        ]);
    }
}