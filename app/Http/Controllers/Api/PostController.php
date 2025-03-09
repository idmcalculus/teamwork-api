<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Access\AuthorizationException;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllPosts(): JsonResponse
    {
        $paginatedPostsList = Post::with('user')->latest()->paginate(10);

        return $this->paginatedResponse($paginatedPostsList);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNewPost(Request $postCreationRequest): JsonResponse
    {
        $postCreationRequest->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:article,gif',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $newPostEntry = new Post();
            $newPostEntry->title = $postCreationRequest->title;
            $newPostEntry->content = $postCreationRequest->content;
            $newPostEntry->type = $postCreationRequest->type;
            $newPostEntry->user_id = Auth::id();

            if ($postCreationRequest->hasFile('image')) {
                $uploadedImagePath = $postCreationRequest->file('image')->store('posts', 'public');
                $newPostEntry->image_url = Storage::url($uploadedImagePath);
            }

            $newPostEntry->save();

            return $this->successResponse(
                $newPostEntry->load('user'),
                'Post created successfully',
                201
            );
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to create post: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostById(string $postId): JsonResponse
    {
        try {
            $requestedPost = Post::with(['user', 'comments.user'])->findOrFail($postId);
            return $this->successResponse($requestedPost);
        } catch (\Exception $exception) {
            return $this->errorResponse('Post not found', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateExistingPost(Request $postUpdateRequest, string $postId): JsonResponse
    {
        try {
            $existingPost = Post::findOrFail($postId);

            // Check if the authenticated user is the owner of the post
            if ($existingPost->user_id !== Auth::id() && !Auth::user()->is_admin) {
                throw new AuthorizationException('Unauthorized. You can only update your own posts.');
            }

            $postUpdateRequest->validate([
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'type' => 'sometimes|required|in:article,gif',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($postUpdateRequest->has('title')) {
                $existingPost->title = $postUpdateRequest->title;
            }

            if ($postUpdateRequest->has('content')) {
                $existingPost->content = $postUpdateRequest->content;
            }

            if ($postUpdateRequest->has('type')) {
                $existingPost->type = $postUpdateRequest->type;
            }

            if ($postUpdateRequest->hasFile('image')) {
                // Delete old image if exists
                if ($existingPost->image_url) {
                    $previousImagePath = str_replace('/storage/', '', $existingPost->image_url);
                    Storage::disk('public')->delete($previousImagePath);
                }

                $newUploadedImagePath = $postUpdateRequest->file('image')->store('posts', 'public');
                $existingPost->image_url = Storage::url($newUploadedImagePath);
            }

            $existingPost->save();

            return $this->successResponse(
                $existingPost->load('user'),
                'Post updated successfully'
            );
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to update post: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePost(string $postId): JsonResponse
    {
        try {
            $postToDelete = Post::findOrFail($postId);

            // Check if the authenticated user is the owner of the post or an admin
            if ($postToDelete->user_id !== Auth::id() && !Auth::user()->is_admin) {
                throw new AuthorizationException('Unauthorized. You can only delete your own posts.');
            }

            // Delete image if exists
            if ($postToDelete->image_url) {
                $postImagePath = str_replace('/storage/', '', $postToDelete->image_url);
                Storage::disk('public')->delete($postImagePath);
            }

            $postToDelete->delete();

            return $this->successResponse(null, 'Post deleted successfully');
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to delete post: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Flag a post as inappropriate.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function flagInappropriatePost(string $postId): JsonResponse
    {
        try {
            $postToFlag = Post::findOrFail($postId);
            $postToFlag->flagged = 'true';
            $postToFlag->save();

            return $this->successResponse(null, 'Post flagged successfully');
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to flag post: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Get posts by a specific user.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostsByUserId(string $userId): JsonResponse
    {
        try {
            $userSpecificPosts = Post::with('user')
                ->where('user_id', $userId)
                ->latest()
                ->paginate(10);

            return $this->paginatedResponse($userSpecificPosts);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to retrieve user posts: ' . $exception->getMessage(), 500);
        }
    }
}
