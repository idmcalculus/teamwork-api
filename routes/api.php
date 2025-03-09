<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // User routes
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::get('/users/{userId}', [UserController::class, 'getUserById']);
    Route::put('/profile', [UserController::class, 'updateUserProfile']);
    Route::post('/change-password', [UserController::class, 'changeUserPassword']);
    Route::put('/users/{userId}/admin-status', [UserController::class, 'updateUserAdminStatus']);
    
    // Post routes
    Route::get('/posts', [PostController::class, 'getAllPosts']);
    Route::post('/posts', [PostController::class, 'createNewPost']);
    Route::get('/posts/{postId}', [PostController::class, 'getPostById']);
    Route::put('/posts/{postId}', [PostController::class, 'updateExistingPost']);
    Route::delete('/posts/{postId}', [PostController::class, 'deletePost']);
    Route::put('/posts/{postId}/flag', [PostController::class, 'flagInappropriatePost']);
    Route::get('/users/{userId}/posts', [PostController::class, 'getPostsByUserId']);
    
    // Comment routes
    Route::get('/posts/{postId}/comments', [CommentController::class, 'getPostComments']);
    Route::post('/posts/{postId}/comments', [CommentController::class, 'createNewComment']);
    Route::get('/posts/{postId}/comments/{commentId}', [CommentController::class, 'getCommentById']);
    Route::put('/posts/{postId}/comments/{commentId}', [CommentController::class, 'updateExistingComment']);
    Route::delete('/posts/{postId}/comments/{commentId}', [CommentController::class, 'deleteComment']);
    Route::get('/users/{userId}/comments', [CommentController::class, 'getCommentsByUserId']);
});
