<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  string  $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(string $postId)
    {
        $post = Post::findOrFail($postId);
        $comments = $post->comments()->with('user')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $comments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $postId)
    {
        $post = Post::findOrFail($postId);

        $request->validate([
            'comment' => 'required|string',
        ]);

        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->user_id = Auth::id();
        $comment->post_id = $post->id;
        $comment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment added successfully',
            'data' => $comment->load('user'),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $postId
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $postId, string $id)
    {
        $post = Post::findOrFail($postId);
        $comment = $post->comments()->with('user')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $comment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $postId
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $postId, string $id)
    {
        $post = Post::findOrFail($postId);
        $comment = $post->comments()->findOrFail($id);

        // Check if the authenticated user is the owner of the comment
        if ($comment->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You can only update your own comments.',
            ], 403);
        }

        $request->validate([
            'comment' => 'required|string',
        ]);

        $comment->comment = $request->comment;
        $comment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment updated successfully',
            'data' => $comment->load('user'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $postId
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $postId, string $id)
    {
        $post = Post::findOrFail($postId);
        $comment = $post->comments()->findOrFail($id);

        // Check if the authenticated user is the owner of the comment or the post or an admin
        if ($comment->user_id !== Auth::id() && $post->user_id !== Auth::id() && !Auth::user()->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You can only delete your own comments or comments on your posts.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Get all comments by a specific user.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserComments(string $userId)
    {
        $comments = Comment::with(['user', 'post'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $comments,
        ]);
    }
}
