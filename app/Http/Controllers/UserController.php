<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Update the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!Auth::check()) {
            throw new Exception('Unauthorized', 401);
        }

        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::createResponse(false, 'Validation failed', null, $validator->errors(), 400);
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->hasFile('image')) {
            if ($user->image_path && Storage::exists('public/' . $user->image_path)) {
                Storage::delete('public/' . $user->image_path);
            }

            $imagePath = $request->file('image')->store('images', 'public');
            $user->image_path = $imagePath;
        }

        $user->save();

        return ResponseHelper::createResponse(true, 'User updated successfully', $user);
    }

    /**
     * Show the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $user = User::find($id);
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        return ResponseHelper::createResponse(true, 'User fetched successfully', $user);
    }

    /**
     * List users with pagination and search.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $search = $request->input('search', '');
        $limit = $request->input('limit', 15);
        $users = User::when($search, function ($query, $search) {
            return $query->where('name', 'like', '%' . $search . '%')
                         ->orWhere('email', 'like', '%' . $search . '%');
        })
        ->paginate($limit); 

        return ResponseHelper::createResponse(true, 'Users fetched successfully', $users);
    }

    /**
     * Get the authenticated user's information.
     *
     * @return \Illuminate\Http\Response
     */
    public function me()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return ResponseHelper::createResponse(true, 'Authenticated user info', $user);
        }

        return ResponseHelper::createResponse(false, 'Unauthorized', null, ['message' => 'You are not authenticated'], 401);
    }
}
