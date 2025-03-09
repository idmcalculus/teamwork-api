<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
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
    public function getPostComments(string $postId): JsonResponse
    {
        try {
            $targetPost = Post::findOrFail($postId);
            $postCommentsList = $targetPost->comments()->with('user')->latest()->get();

            return $this->successResponse($postCommentsList);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to retrieve comments: ' . $exception->getMessage(), 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNewComment(Request $commentCreationRequest, string $postId): JsonResponse
    {
        try {
            $targetPost = Post::findOrFail($postId);

            $commentCreationRequest->validate([
                'comment' => 'required|string',
            ]);

            $newCommentEntry = new Comment();
            $newCommentEntry->comment = $commentCreationRequest->comment;
            $newCommentEntry->user_id = Auth::id();
            $newCommentEntry->post_id = $targetPost->id;
            $newCommentEntry->save();

            return $this->successResponse(
                $newCommentEntry->load('user'),
                'Comment added successfully',
                201
            );
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to create comment: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $postId
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCommentById(string $postId, string $commentId): JsonResponse
    {
        try {
            $targetPost = Post::findOrFail($postId);
            $requestedComment = $targetPost->comments()->with('user')->findOrFail($commentId);

            return $this->successResponse($requestedComment);
        } catch (\Exception $exception) {
            return $this->errorResponse('Comment not found', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $postId
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateExistingComment(Request $commentUpdateRequest, string $postId, string $commentId): JsonResponse
    {
        try {
            $targetPost = Post::findOrFail($postId);
            $existingComment = $targetPost->comments()->findOrFail($commentId);

            // Check if the authenticated user is the owner of the comment
            if ($existingComment->user_id !== Auth::id() && !Auth::user()->is_admin) {
                throw new AuthorizationException('Unauthorized. You can only update your own comments.');
            }

            $commentUpdateRequest->validate([
                'comment' => 'required|string',
            ]);

            $existingComment->comment = $commentUpdateRequest->comment;
            $existingComment->save();

            return $this->successResponse(
                $existingComment->load('user'),
                'Comment updated successfully'
            );
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to update comment: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $postId
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteComment(string $postId, string $commentId): JsonResponse
    {
        try {
            $targetPost = Post::findOrFail($postId);
            $commentToDelete = $targetPost->comments()->findOrFail($commentId);

            // Check if the authenticated user is the owner of the comment or the post or an admin
            if ($commentToDelete->user_id !== Auth::id() && $targetPost->user_id !== Auth::id() && !Auth::user()->is_admin) {
                throw new AuthorizationException('Unauthorized. You can only delete your own comments or comments on your posts.');
            }

            $commentToDelete->delete();

            return $this->successResponse(null, 'Comment deleted successfully');
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to delete comment: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Get all comments by a specific user.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCommentsByUserId(string $userId): JsonResponse
    {
        try {
            $userSpecificComments = Comment::with(['user', 'post'])
                ->where('user_id', $userId)
                ->latest()
                ->get();

            return $this->successResponse($userSpecificComments);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to retrieve user comments: ' . $exception->getMessage(), 500);
        }
    }
}
