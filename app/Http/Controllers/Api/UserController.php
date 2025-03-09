<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Get all users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = User::paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    /**
     * Get a specific user.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
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
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('department')) {
            $user->department = $request->department;
        }

        if ($request->has('job_role')) {
            $user->job_role = $request->job_role;
        }

        if ($request->has('bio')) {
            $user->bio = $request->bio;
        }

        if ($request->has('address')) {
            $user->address = $request->address;
        }

        if ($request->has('gender')) {
            $user->gender = $request->gender;
        }

        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                $oldPath = str_replace('/storage/', '', $user->avatar);
                Storage::disk('public')->delete($oldPath);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = Storage::url($avatarPath);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Change the authenticated user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Update user admin status (admin only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAdminStatus(Request $request, string $id)
    {
        // Check if authenticated user is an admin
        if (!Auth::user()->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only admins can perform this action.',
            ], 403);
        }

        $request->validate([
            'is_admin' => 'required|boolean',
        ]);

        $user = User::findOrFail($id);
        $user->is_admin = $request->is_admin;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User admin status updated successfully',
            'data' => $user,
        ]);
    }
}
