<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\WeeklyAssessment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $assessments = $request->user()
            ->weeklyAssessments()
            ->orderBy('week_start', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => $assessments->items(),
            'meta' => [
                'current_page' => $assessments->currentPage(),
                'last_page' => $assessments->lastPage(),
                'per_page' => $assessments->perPage(),
                'total' => $assessments->total(),
            ],
        ]);
    }

    public function store(StoreAssessmentRequest $request): JsonResponse
    {
        $assessment = $request->user()->weeklyAssessments()->create([
            'week_start' => $request->week_start,
            'week_end' => $request->week_end,
            'ratings' => $request->ratings,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'data' => $assessment,
            'message' => 'Assessment created successfully',
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $assessment = $request->user()
            ->weeklyAssessments()
            ->findOrFail($id);

        return response()->json([
            'data' => $assessment,
        ]);
    }

    public function update(UpdateAssessmentRequest $request, string $id): JsonResponse
    {
        $assessment = $request->user()
            ->weeklyAssessments()
            ->findOrFail($id);

        $assessment->update($request->validated());

        return response()->json([
            'data' => $assessment->fresh(),
            'message' => 'Assessment updated successfully',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $assessment = $request->user()
            ->weeklyAssessments()
            ->findOrFail($id);

        $assessment->delete();

        return response()->json([
            'message' => 'Assessment deleted successfully',
        ]);
    }

    public function currentWeek(Request $request): JsonResponse
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $assessment = $request->user()
            ->weeklyAssessments()
            ->where('week_start', $weekStart)
            ->first();

        if (!$assessment) {
            return response()->json([
                'data' => null,
                'meta' => [
                    'week_start' => $weekStart,
                    'week_end' => $weekEnd,
                ],
            ]);
        }

        return response()->json([
            'data' => $assessment,
        ]);
    }
}
