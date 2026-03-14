<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $prefs = NotificationPreference::firstOrCreate(
            ['user_id' => $request->user_id],
            [
                'weekly_wheel_enabled' => true,
                'weekly_wheel_time'    => '20:00',
                'deadline_enabled'     => true,
                'deadline_days_before' => 1,
                'custom_enabled'       => false,
                'custom_text'          => null,
                'custom_time'          => '09:00',
                'custom_days'          => [1, 3, 5],
            ]
        );

        return response()->json($prefs);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'weekly_wheel_enabled' => 'sometimes|boolean',
            'weekly_wheel_time'    => 'sometimes|string|regex:/^\d{2}:\d{2}$/',
            'deadline_enabled'     => 'sometimes|boolean',
            'deadline_days_before' => 'sometimes|integer|min:0|max:7',
            'custom_enabled'       => 'sometimes|boolean',
            'custom_text'          => 'sometimes|nullable|string|max:255',
            'custom_time'          => 'sometimes|string|regex:/^\d{2}:\d{2}$/',
            'custom_days'          => 'sometimes|array',
            'custom_days.*'        => 'integer|min:0|max:6',
        ]);

        $prefs = NotificationPreference::updateOrCreate(
            ['user_id' => $request->user_id],
            $validated
        );

        return response()->json($prefs);
    }
}
