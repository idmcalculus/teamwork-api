<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $posts = Post::with('user')->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $posts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:article,gif',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $post = new Post();
        $post->title = $request->title;
        $post->content = $request->content;
        $post->type = $request->type;
        $post->user_id = Auth::id();

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
            $post->image_url = Storage::url($imagePath);
        }

        $post->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Post created successfully',
            'data' => $post->load('user'),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $post = Post::with(['user', 'comments.user'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $post,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $post = Post::findOrFail($id);

        // Check if the authenticated user is the owner of the post
        if ($post->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You can only update your own posts.',
            ], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:article,gif',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->has('title')) {
            $post->title = $request->title;
        }

        if ($request->has('content')) {
            $post->content = $request->content;
        }

        if ($request->has('type')) {
            $post->type = $request->type;
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($post->image_url) {
                $oldPath = str_replace('/storage/', '', $post->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $imagePath = $request->file('image')->store('posts', 'public');
            $post->image_url = Storage::url($imagePath);
        }

        $post->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Post updated successfully',
            'data' => $post->load('user'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $post = Post::findOrFail($id);

        // Check if the authenticated user is the owner of the post or an admin
        if ($post->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You can only delete your own posts.',
            ], 403);
        }

        // Delete image if exists
        if ($post->image_url) {
            $imagePath = str_replace('/storage/', '', $post->image_url);
            Storage::disk('public')->delete($imagePath);
        }

        $post->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Post deleted successfully',
        ]);
    }

    /**
     * Flag a post as inappropriate.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function flagPost(string $id)
    {
        $post = Post::findOrFail($id);
        $post->flagged = 'true';
        $post->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Post flagged successfully',
        ]);
    }

    /**
     * Get posts by a specific user.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPosts(string $userId)
    {
        $posts = Post::with('user')
            ->where('user_id', $userId)
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $posts,
        ]);
    }
}
