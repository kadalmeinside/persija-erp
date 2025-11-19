<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;  
use App\Http\Requests\Admin\UpdateUserRequest; 
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;   
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // public function __construct()
    // {
    //     // Bisa aktifkan ini jika pakai policy
    //     // $this->authorizeResource(User::class, 'user');
    // }

    public function index(Request $request)
    {
        $query = User::with('roles')->orderBy('name');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role') && $request->input('role') !== '') {
            $roleName = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        $users = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles_array' => $user->roles->pluck('name')->toArray(),
                    'roles_string' => $user->roles->pluck('name')->implode(', '),
                ];
            }),
            'filters' => $request->only(['search', 'role']),
            'allRoles' => Role::orderBy('name')->pluck('name'),
            'can' => [
                'create_user' => $request->user()->can('manage users'),
                'edit_user' => $request->user()->can('manage users'),
                'delete_user' => $request->user()->can('manage users'),
            ],
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return Redirect::route('admin.users.index', $request->only(['search', 'role']))
            ->with([
                'message' => 'User created successfully.',
                'type' => 'success',
            ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        } else {
            $user->syncRoles([]);
        }

        return Redirect::route('admin.users.index', $request->only(['search', 'role']))
            ->with([
                'message' => 'User updated successfully.',
                'type' => 'success',
            ]);
    }

    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->can('manage users')) {
            abort(403);
        }

        $user->delete();

        return Redirect::route('admin.users.index', $request->only(['search', 'role']))
            ->with([
                'message' => 'User deleted successfully.',
                'type' => 'success',
            ]);
    }
}