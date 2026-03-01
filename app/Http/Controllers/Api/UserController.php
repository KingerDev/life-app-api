<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'avatar_url' => 'sometimes|nullable|string|url|max:500',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'User updated successfully',
        ]);
    }
}
