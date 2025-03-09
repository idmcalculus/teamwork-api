<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Get all users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllUsers(): JsonResponse
    {
        try {
            $paginatedUsersList = User::paginate(10);
            return $this->paginatedResponse($paginatedUsersList);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to retrieve users: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Get a specific user.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserById(string $userId): JsonResponse
    {
        try {
            $requestedUser = User::findOrFail($userId);
            return $this->successResponse($requestedUser);
        } catch (\Exception $exception) {
            return $this->errorResponse('User not found', 404);
        }
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserProfile(Request $profileUpdateRequest): JsonResponse
    {
        try {
            /** @var \App\Models\User $authenticatedUser */
            $authenticatedUser = Auth::user();

            $profileUpdateRequest->validate([
                'name' => 'sometimes|string|max:255',
                'department' => 'sometimes|string|max:255',
                'job_role' => 'sometimes|string|max:255',
                'bio' => 'sometimes|string',
                'address' => 'sometimes|string',
                'gender' => 'sometimes|string|in:male,female,other',
                'phone' => 'sometimes|string',
                'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Update user fields if they exist in the request
            if ($profileUpdateRequest->has('name')) {
                $authenticatedUser->name = $profileUpdateRequest->name;
            }

            if ($profileUpdateRequest->has('department')) {
                $authenticatedUser->department = $profileUpdateRequest->department;
            }

            if ($profileUpdateRequest->has('job_role')) {
                $authenticatedUser->job_role = $profileUpdateRequest->job_role;
            }

            if ($profileUpdateRequest->has('bio')) {
                $authenticatedUser->bio = $profileUpdateRequest->bio;
            }

            if ($profileUpdateRequest->has('address')) {
                $authenticatedUser->address = $profileUpdateRequest->address;
            }

            if ($profileUpdateRequest->has('gender')) {
                $authenticatedUser->gender = $profileUpdateRequest->gender;
            }

            if ($profileUpdateRequest->has('phone')) {
                $authenticatedUser->phone = $profileUpdateRequest->phone;
            }

            // Handle avatar upload
            if ($profileUpdateRequest->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($authenticatedUser->avatar) {
                    $previousAvatarPath = str_replace('/storage/', '', $authenticatedUser->avatar);
                    Storage::disk('public')->delete($previousAvatarPath);
                }

                $newAvatarPath = $profileUpdateRequest->file('avatar')->store('avatars', 'public');
                $authenticatedUser->avatar = Storage::url($newAvatarPath);
            }

            $authenticatedUser->save();

            return $this->successResponse($authenticatedUser, 'Profile updated successfully');
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to update profile: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Change the authenticated user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeUserPassword(Request $passwordChangeRequest): JsonResponse
    {
        try {
            $passwordChangeRequest->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            /** @var \App\Models\User $authenticatedUser */
            $authenticatedUser = Auth::user();

            // Check if current password is correct
            if (!Hash::check($passwordChangeRequest->current_password, $authenticatedUser->password)) {
                throw new ValidationException(validator([], []), 'Current password is incorrect');
            }

            $authenticatedUser->password = Hash::make($passwordChangeRequest->password);
            $authenticatedUser->save();

            return $this->successResponse(null, 'Password changed successfully');
        } catch (ValidationException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to change password: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Update user admin status (admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserAdminStatus(Request $adminStatusUpdateRequest, string $userId): JsonResponse
    {
        try {
            // Check if authenticated user is an admin
            if (!Auth::user()->is_admin) {
                throw new AuthorizationException('Unauthorized. Only admins can perform this action.');
            }

            $adminStatusUpdateRequest->validate([
                'is_admin' => 'required|boolean',
            ]);

            $targetUser = User::findOrFail($userId);
            $targetUser->is_admin = $adminStatusUpdateRequest->is_admin;
            $targetUser->save();

            return $this->successResponse($targetUser, 'User admin status updated successfully');
        } catch (AuthorizationException $exception) {
            return $this->errorResponse($exception->getMessage(), 403);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to update admin status: ' . $exception->getMessage(), 500);
        }
    }
}
