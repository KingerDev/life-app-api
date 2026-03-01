<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestRequest;
use App\Http\Requests\UpdateQuestRequest;
use App\Models\QuarterlyQuest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $quests = $request->user()
            ->quarterlyQuests()
            ->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $quests->items(),
            'meta' => [
                'current_page' => $quests->currentPage(),
                'last_page' => $quests->lastPage(),
                'per_page' => $quests->perPage(),
                'total' => $quests->total(),
            ],
        ]);
    }

    public function store(StoreQuestRequest $request): JsonResponse
    {
        // Check if quest already exists for this quarter/year/type
        $existing = $request->user()
            ->quarterlyQuests()
            ->where('quarter', $request->quarter)
            ->where('year', $request->year)
            ->where('type', $request->type)
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'Quest already exists for this quarter',
                'data' => $existing,
            ], 409);
        }

        $quest = $request->user()->quarterlyQuests()->create([
            'quarter' => $request->quarter,
            'year' => $request->year,
            'type' => $request->type,
            'discovery_answers' => $request->discovery_answers,
            'main_goal' => $request->main_goal,
            'why_important' => $request->why_important,
            'success_criteria' => $request->success_criteria,
            'excitement' => $request->excitement,
            'commitment' => $request->commitment,
        ]);

        return response()->json([
            'data' => $quest,
            'message' => 'Quest created successfully',
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $quest = $request->user()
            ->quarterlyQuests()
            ->findOrFail($id);

        return response()->json([
            'data' => $quest,
        ]);
    }

    public function update(UpdateQuestRequest $request, string $id): JsonResponse
    {
        $quest = $request->user()
            ->quarterlyQuests()
            ->findOrFail($id);

        $quest->update($request->validated());

        return response()->json([
            'data' => $quest->fresh(),
            'message' => 'Quest updated successfully',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $quest = $request->user()
            ->quarterlyQuests()
            ->findOrFail($id);

        $quest->delete();

        return response()->json([
            'message' => 'Quest deleted successfully',
        ]);
    }

    public function currentQuarter(Request $request): JsonResponse
    {
        $now = Carbon::now();
        $quarter = ceil($now->month / 3);
        $year = $now->year;

        $quests = $request->user()
            ->quarterlyQuests()
            ->where('quarter', $quarter)
            ->where('year', $year)
            ->get();

        $workQuest = $quests->firstWhere('type', 'work');
        $lifeQuest = $quests->firstWhere('type', 'life');

        return response()->json([
            'data' => [
                'work' => $workQuest,
                'life' => $lifeQuest,
            ],
            'meta' => [
                'quarter' => $quarter,
                'year' => $year,
            ],
        ]);
    }
}
