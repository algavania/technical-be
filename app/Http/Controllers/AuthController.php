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

            return ResponseHelper::createResponse(
                true,
                'Login successful',
                [
                    'user' => $user,
                    'token' => $token,
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
            'token' => 'required',
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

        // Reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ResponseHelper::createResponse(
                true,
                'Password reset successfully.',
                null,
                null,
                200
            );
        } else {
            throw new \Exception('Password reset failed.', 500);
        }
    }
}
