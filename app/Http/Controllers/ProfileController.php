<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'data' => $validator->errors()->first()], 200);
        }

        try {
            $user->name = $request->name;
            $user->email = $request->email;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
            Log::info('Profile updated', ['user_id' => $user->id, 'changed' => array_keys($user->getChanges())]);

            return response()->json(['status' => true, 'data' => 'Profile updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => 'Failed to update profile'], 200);
        }
    }
}
