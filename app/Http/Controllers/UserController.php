<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the users with search and pagination.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::with('roles')
            ->when($search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString(); // Maintains search parameter during pagination

        return view('users.index', compact('users', 'search'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $partners = Partner::all();
        $roles = Role::all();
        return view('users.create', compact('roles', 'partners'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'mobile'   => 'nullable|string|max:20', // Added mobile validation
            'password' => ['required', Password::defaults()],
            'roles'    => 'required|array',
            'partner_id' => 'nullable|exists:partners,id'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'mobile'   => $request->mobile, // Store mobile
            'password' => Hash::make($request->password),
            'partner_id' => $request->partner_id,
        ]);

        $user->assignRole($request->roles);

        return redirect()->route('users.index')
            ->with('success', 'User created and roles assigned successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $partners = Partner::all();
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles', 'partners'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'mobile' => 'nullable|string|max:20', // Added mobile validation
            'roles'  => 'required|array',
            'partner_id' => 'nullable|exists:partners,id'
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile = $request->mobile; // Update mobile
        $user->partner_id = $request->partner_id; // Update partner_id

        if ($request->filled('password')) {
            $request->validate(['password' => Password::defaults()]);
            $user->password = Hash::make($request->password);
        }

        $user->save();
        $user->syncRoles($request->roles);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}