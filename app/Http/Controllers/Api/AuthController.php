<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'department' => 'nullable|string|max:255',
                'job_role' => 'nullable|string|max:255',
                'gender' => 'nullable|string|in:male,female,other',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'department' => $request->department,
                'job_role' => $request->job_role,
                'gender' => $request->gender,
                'address' => $request->address,
                'phone' => $request->phone,
                'is_admin' => false, // Default value, only admins can change this
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse(
                [
                    'user' => $user,
                    'token' => $token,
                ],
                'User registered successfully',
                201
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse('Validation failed', 422, $exception->errors());
        } catch (\Exception $exception) {
            return $this->errorResponse('Registration failed: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Login a user and create a token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse(
                [
                    'user' => $user,
                    'token' => $token,
                ],
                'User logged in successfully'
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse('Authentication failed', 422, $exception->errors());
        } catch (\Exception $exception) {
            return $this->errorResponse('Login failed: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Logout a user and revoke token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse(null, 'User logged out successfully');
        } catch (\Exception $exception) {
            return $this->errorResponse('Logout failed: ' . $exception->getMessage(), 500);
        }
    }

    /**
     * Get the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        try {
            return $this->successResponse(['user' => $request->user()]);
        } catch (\Exception $exception) {
            return $this->errorResponse('Failed to retrieve user data: ' . $exception->getMessage(), 500);
        }
    }
}
