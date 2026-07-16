<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function colorPreferences(): JsonResponse
    {
        $availableColors = User::availableColorPreferences();

        return response()->json([
            'colors' => User::COLOR_PREFERENCES,
            'available_colors' => $availableColors,
            'taken_colors' => array_values(array_diff(User::COLOR_PREFERENCES, $availableColors)),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:8|confirmed',
            'color_preference' => 'nullable|string|in:' . implode(',', User::COLOR_PREFERENCES),
        ]);

        $colorPreference = $request->color_preference;

        // If no color preference is provided, find the next available one
        if (!$colorPreference) {
            $colorPreference = User::nextAvailableColorPreference();
        }

        // If a color preference was provided, check if it's available
        if ($colorPreference && User::where('color_preference', $colorPreference)->exists()) {
            throw ValidationException::withMessages([
                'color_preference' => ['This color preference is already taken.'],
            ]);
        }

        if ($colorPreference === null) {
            throw ValidationException::withMessages([
                'color_preference' => ['No color preferences are available right now.'],
            ]);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'color_preference' => $colorPreference,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user  = Auth::user();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::ResetLinkSent) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => __($status)]);
    }
}
