<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Notifications\CustomPasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::createResponse(
                false,
                'Validation failed',
                null,
                $validator->errors(),
                400
            );
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return ResponseHelper::createResponse(
            true,
            'User registered successfully',
            $user,
            null,
            201
        );
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::createResponse(
                false,
                'Validation failed',
                null,
                $validator->errors(),
                400
            );
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $token = $user->createToken('API Token')->plainTextToken;
            $refreshToken = Str::random(60);
            $user->refresh_token = $refreshToken;
            $user->save();

            return ResponseHelper::createResponse(
                true,
                'Login successful',
                [
                    'user' => $user,
                    'token' => $token,
                    'refresh_token' => $refreshToken,
                ],
                null,
                200
            );
        }

        // Add invalid credentials response
        if (User::where('email', $request->email)->exists()) {
            throw new \Exception('Invalid password.', 401);
        }

        throw new \Exception('User not found.', 404);
    }

    public function refreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::createResponse(
                false,
                'Validation failed',
                null,
                $validator->errors(),
                400
            );
        }

        $user = User::where('refresh_token', $request->refresh_token)->first();

        if (!$user) {
            return ResponseHelper::createResponse(
                false,
                'The refresh token is invalid or expired',
                null,
                null,
                401
            );
        }

        $accessToken = $user->createToken('API Token')->plainTextToken;
        $newRefreshToken = Str::random(60);

        $user->refresh_token = $newRefreshToken;
        $user->save();

        return ResponseHelper::createResponse(
            true,
            'Token refreshed successfully',
            [
                'token' => $accessToken,
                'refresh_token' => $newRefreshToken,
            ],
            null,
            200
        );
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->tokens->each(function ($token) {
                $token->delete();
            });

            return ResponseHelper::createResponse(
                true,
                'Logout successful',
                null,
                null,
                200
            );
        }

        throw new \Exception('You are not logged in.', 401);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::createResponse(
                false,
                'Validation failed',
                null,
                $validator->errors(),
                400
            );
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return ResponseHelper::createResponse(
                false,
                'Current password is incorrect',
                null,
                null,
                401
            );
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return ResponseHelper::createResponse(
            true,
            'Password updated successfully',
            null,
            null,
            200
        );
    }

    public function sendResetLinkEmail(Request $request)
    {
        // Validate email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::createResponse(
                false,
                'Validation failed',
                null,
                $validator->errors(),
                400
            );
        }

        // Retrieve user
        $user = User::where('email', $request->email)->first();

        // Generate the reset token
        $token = app('auth.password.broker')->createToken($user);

        // Send the custom password reset notification
        $user->notify(new CustomPasswordReset($token));

        return ResponseHelper::createResponse(
            true,
            'Password reset link sent successfully.',
            null,
            null,
            200
        );
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::createResponse(
                false,
                'Validation failed',
                null,
                $validator->errors(),
                400
            );
        }

        // Reset the password using the provided token
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        // Check if the password was successfully reset
        if ($status === Password::PASSWORD_RESET) {
            return ResponseHelper::createResponse(
                true,
                'Password reset successfully.',
                null,
                null,
                200
            );
        } else {
            return ResponseHelper::createResponse(
                false,
                'Password reset failed. The token may be invalid or expired.',
                null,
                null,
                400
            );
        }
    }
}
