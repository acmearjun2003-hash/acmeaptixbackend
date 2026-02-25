<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->select(
                'users.*',
                'roles.name as role_name',
                DB::raw('(
                    SELECT post_master.post_name
                    FROM post_master
                    WHERE post_master.post_id = users.post
                    ORDER BY post_master.created_at DESC
                    LIMIT 1
                ) as post_name')
            )
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id');

        // ── Search (Voyager passes: s=value, key=column, filter=equals|contains) ──
        if ($request->filled('s') && $request->filled('key') && $request->filled('filter')) {
            $operator    = $request->filter === 'equals' ? '=' : 'LIKE';
            $searchValue = $request->filter === 'equals'
                ? $request->s
                : '%' . $request->s . '%';

            $query->where('users.' . $request->key, $operator, $searchValue);
        }

        // ── Legacy / direct filters ──
        $query->when(
            $request->role_id,
            fn($q) => $q->where('users.role_id', $request->role_id)
        );

        // ── Soft deletes ──
        if ($request->boolean('showSoftDeleted') && in_array(SoftDeletes::class, class_uses_recursive(User::class))) {
            $query->withTrashed();
        }

        // ── Ordering ──
        $orderBy   = $request->get('order_by', 'users.id');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($orderBy, $sortOrder);

        // ── Pagination (Voyager uses Laravel's default ?page=N) ──
        // $perPage = $request->get('per_page', 5);
        // $users   = $query->paginate($perPage);

        $users = $query->get();
        return response()->json($users);
    }

    

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id'          => 'nullable|integer|exists:roles,id',
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:users,email',
            'mobileno'         => 'nullable|string|max:25',
            'password'         => 'required|string|min:8|confirmed',
            'avatar'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'settings'         => 'nullable|array',
            'highestquali'     => 'nullable|string|max:50',
            'ssc'              => 'nullable|string|max:10',
            'hsc'              => 'nullable|string|max:10',
            'diploma'          => 'nullable|string|max:10',
            'degree'           => 'nullable|string|max:10',
            'masterdegree'     => 'nullable|string|max:10',
            'aptiscore'        => 'nullable|integer',
            'examstarted'      => 'nullable|integer',
            'aptidate'         => 'nullable|integer',
            'aptitime'         => 'nullable|integer',
            'techroundpercent' => 'nullable|numeric|between:0,9999.99',
            'interviewpercent' => 'nullable|numeric|between:0,9999.99',
            'referenceby'      => 'nullable|string|max:100',
            'document'         => 'nullable|file|max:5120',
            'post'             => 'nullable|integer',
        ]);

        // Hash password
        $validated['password'] = Hash::make($validated['password']);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('users', 'public');
        }

        // Handle document upload
        if ($request->hasFile('document')) {
            $validated['document'] = file_get_contents($request->file('document')->getRealPath());
        }

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load('role');

        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id'          => 'nullable|integer|exists:roles,id',
            'name'             => 'sometimes|required|string|max:255',
            'email'            => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobileno'         => 'nullable|string|max:25',
            'password'         => 'sometimes|nullable|string|min:8|confirmed',
            'avatar'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'settings'         => 'nullable|array',
            'highestquali'     => 'nullable|string|max:50',
            'ssc'              => 'nullable|string|max:10',
            'hsc'              => 'nullable|string|max:10',
            'diploma'          => 'nullable|string|max:10',
            'degree'           => 'nullable|string|max:10',
            'masterdegree'     => 'nullable|string|max:10',
            'aptiscore'        => 'nullable|integer',
            'examstarted'      => 'nullable|integer',
            'aptidate'         => 'nullable|integer',
            'aptitime'         => 'nullable|integer',
            'techroundpercent' => 'nullable|numeric|between:0,9999.99',
            'interviewpercent' => 'nullable|numeric|between:0,9999.99',
            'referenceby'      => 'nullable|string|max:100',
            'document'         => 'nullable|file|max:5120',
            'post'             => 'nullable|integer',
        ]);

        // Hash password only if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if not default
            if ($user->avatar && $user->avatar !== 'users/default.png') {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('users', 'public');
        }

        // Handle document upload
        if ($request->hasFile('document')) {
            $validated['document'] = file_get_contents($request->file('document')->getRealPath());
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'user'    => $user->fresh(),
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Delete avatar if not default
        if ($user->avatar && $user->avatar !== 'users/default.png') {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    /**
     * Update the user's exam / aptitude results.
     */
    public function updateExamResults(Request $request, User $user)
    {
        $validated = $request->validate([
            'aptiscore'        => 'required|integer',
            'examstarted'      => 'required|integer',
            'aptidate'         => 'required|integer',
            'aptitime'         => 'required|integer',
            'techroundpercent' => 'required|numeric|between:0,9999.99',
            'interviewpercent' => 'required|numeric|between:0,9999.99',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Exam results updated successfully.',
            'user'    => $user->fresh(),
        ]);
    }
}
