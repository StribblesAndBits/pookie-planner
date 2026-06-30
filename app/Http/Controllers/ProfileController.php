<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

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
            'color_preference'  => 'string|in:blue,green,pink,yellow',
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

        return response()->json($user);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}

