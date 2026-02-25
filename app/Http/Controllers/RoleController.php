<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * GET /roles
     * List all roles with their user counts.
     */
    public function index()
    {
        $roles = Role::withCount('users')->get();

        return response()->json($roles);
    }

    public function fetchUniqueRoles()
    {
        $roles = Role::distinct()           // or ->groupBy() / ->pluck()
            ->pluck('id','display_name');       // or 'display_name', 'slug' etc.

        return response()->json($roles);
    }
    /**
     * POST /roles
     * Create a new role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
        ]);

        $role = Role::create($validated);

        return response()->json($role, 201);
    }

    /**
     * GET /roles/{id}
     * Show a role and all users belonging to it.
     */
    public function show(int $id)
    {
        $role = Role::with('users')->findOrFail($id);

        return response()->json($role);
    }

    /**
     * PUT /roles/{id}
     * Update a role.
     */
    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255|unique:roles,name,' . $id,
            'display_name' => 'sometimes|string|max:255',
        ]);

        $role->update($validated);

        return response()->json($role);
    }

    /**
     * DELETE /roles/{id}
     * Delete a role (users will have role_id set to NULL via FK cascade).
     */
    public function destroy(int $id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }
}
