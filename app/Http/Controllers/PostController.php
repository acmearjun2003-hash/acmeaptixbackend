<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * GET /posts
     * List all posts. Pass ?active=1 to filter active posts only.
     */
    public function index(Request $request)
    {
        $query = Post::withCount('users');

        if ($request->boolean('active')) {
            $query->active();
        }

        return response()->json($query->get());
    }

    /**
     * POST /posts
     * Create a new post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_name'  => 'required|string|max:100',
            'department' => 'required|string|max:80',
            'is_active'  => 'boolean',
        ]);

        $post = Post::create($validated);

        return response()->json($post, 201);
    }

    /**
     * GET /posts/{id}
     * Show a post along with the candidates who applied for it.
     */
    public function show(int $id)
    {
        $post = Post::with('users.role')->findOrFail($id);

        return response()->json($post);
    }

    /**
     * PUT /posts/{id}
     * Update a post.
     */
    public function update(Request $request, int $id)
    {
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'post_name'  => 'sometimes|string|max:100',
            'department' => 'sometimes|string|max:80',
            'is_active'  => 'boolean',
        ]);

        $post->update($validated);

        return response()->json($post);
    }

    /**
     * DELETE /posts/{id}
     * Soft-deactivate a post instead of hard delete to preserve FK integrity.
     */
    public function destroy(int $id)
    {
        $post = Post::findOrFail($id);
        $post->update(['is_active' => false]);

        return response()->json(['message' => 'Post deactivated successfully.']);
    }
}