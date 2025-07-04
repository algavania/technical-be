<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    // Display the password reset form
    public function showResetForm()
    {
        // token is from query param
        $token = request()->query('token');
        return view('auth.passwords.reset', ['token' => $token]);
    }

    // Handle the password reset process
    public function resetPassword(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Reset the password using the provided token
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect('/')->with('status', 'Password reset successfully!');
        } else {
            return back()->withErrors(['email' => trans($status)]);
        }
    }
}
