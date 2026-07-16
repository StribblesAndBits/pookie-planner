<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'first_name'        => 'string|max:255',
            'last_name'         => 'string|max:255',
            'email'             => 'string|email|max:255|unique:users,email,' . $user->id,
            'password'          => 'string|min:8|confirmed',
            'color_preference'  => [
                'nullable',
                'string',
                Rule::in(User::COLOR_PREFERENCES),
                Rule::unique('users', 'color_preference')->ignore($user->id),
            ],
        ]);

        if ($request->filled('first_name')) {
            $user->first_name = $request->first_name;
        }

        if ($request->filled('last_name')) {
            $user->last_name = $request->last_name;
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->filled('color_preference')) {
            $user->color_preference = $request->color_preference;
        }

        $user->save();

        return response()->json([
            'user' => $user,
            'available_colors' => User::availableColorPreferences($user->id),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
            'available_colors' => User::availableColorPreferences($request->user()->id),
        ]);
    }
}
