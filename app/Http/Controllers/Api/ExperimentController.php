<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExperimentCheckIn;
use App\Models\TinyExperiment;
use App\Models\WeeklyAssessment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExperimentController extends Controller
{
    // Mapping from Wheel of Life aspects to Beliefs domains (for suggestions)
    private const WOL_TO_BELIEFS_DOMAIN = [
        'career' => 'career',
        'physical_health' => 'health',
        'mental_health' => 'health',
        'family_friends' => 'relationships',
        'romantic_life' => 'relationships',
        'finances' => 'money',
        'personal_growth' => 'learning',
        'purpose' => 'impact',
    ];

    private const DOMAIN_LABELS = [
        'career' => 'Kariéra',
        'relationships' => 'Vzťahy',
        'health' => 'Zdravie',
        'creativity' => 'Kreativita',
        'learning' => 'Učenie',
        'money' => 'Peniaze',
        'confidence' => 'Sebadôvera',
        'impact' => 'Dopad',
    ];

    private const ASPECT_LABELS = [
        'physical_health' => 'Fyzické zdravie',
        'mental_health' => 'Mentálne zdravie',
        'family_friends' => 'Rodina a priatelia',
        'romantic_life' => 'Romantický život',
        'career' => 'Kariéra',
        'finances' => 'Financie',
        'personal_growth' => 'Osobný rast',
        'purpose' => 'Zmysel života',
    ];

    /**
     * Get active experiments for authenticated user
     * GET /api/experiments
     */
    public function index(Request $request): JsonResponse
    {
        $experiments = $request->user()
            ->tinyExperiments()
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $experiments,
        ]);
    }

    /**
     * Get suggestions based on Wheel of Life
     * GET /api/experiments/suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        $latestAssessment = $request->user()
            ->weeklyAssessments()
            ->orderBy('week_start', 'desc')
            ->first();

        if (!$latestAssessment) {
            return response()->json(['data' => []]);
        }

        $suggestions = [];
        $ratings = is_array($latestAssessment->ratings) ? $latestAssessment->ratings : [];

        foreach ($ratings as $rating) {
            if (isset($rating['score']) && $rating['score'] < 7) {
                $aspectId = $rating['aspect_id'] ?? '';
                $domainId = self::WOL_TO_BELIEFS_DOMAIN[$aspectId] ?? null;

                if ($domainId) {
                    $suggestions[] = [
                        'domain_id' => $domainId,
                        'domain_label' => self::DOMAIN_LABELS[$domainId] ?? $domainId,
                        'source' => 'wheel_of_life',
                        'reason' => 'Nízke skóre: ' . (self::ASPECT_LABELS[$aspectId] ?? $aspectId) . ' (' . $rating['score'] . '/10)',
                        'aspect_score' => $rating['score'],
                        'priority' => $rating['score'],
                    ];
                }
            }
        }

        // Sort by score (ascending) - lower score = higher priority
        usort($suggestions, fn($a, $b) => $a['priority'] <=> $b['priority']);

        // Take top 3
        $suggestions = array_slice($suggestions, 0, 3);

        return response()->json(['data' => $suggestions]);
    }

    /**
     * Get paginated history (completed/abandoned experiments)
     * GET /api/experiments/history?page=N
     */
    public function history(Request $request): JsonResponse
    {
        $experiments = $request->user()
            ->tinyExperiments()
            ->whereIn('status', ['completed', 'abandoned'])
            ->orderBy('end_date', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $experiments->items(),
            'meta' => [
                'current_page' => $experiments->currentPage(),
                'last_page' => $experiments->lastPage(),
                'per_page' => $experiments->perPage(),
                'total' => $experiments->total(),
            ],
        ]);
    }

    /**
     * Get single experiment
     * GET /api/experiments/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $experiment = $request->user()
            ->tinyExperiments()
            ->findOrFail($id);

        return response()->json([
            'data' => $experiment,
        ]);
    }

    /**
     * Create new experiment
     * POST /api/experiments
     */
    public function store(Request $request): JsonResponse
    {
        // Check limit: max 3 active experiments
        $activeCount = $request->user()
            ->tinyExperiments()
            ->where('status', 'active')
            ->count();

        if ($activeCount >= 3) {
            return response()->json([
                'error' => 'Maximum 3 active experiments allowed',
            ], 422);
        }

        $validated = $request->validate([
            'domain_id' => ['required', 'string'],
            'field_notes' => ['required', 'array'],
            'field_notes.what_felt_good' => ['nullable', 'string'],
            'field_notes.what_didnt_feel_good' => ['nullable', 'string'],
            'field_notes.curiosities' => ['nullable', 'string'],
            'field_notes.inspiring_people' => ['nullable', 'string'],
            'field_notes.flow_work' => ['nullable', 'string'],
            'field_notes.procrastination_work' => ['nullable', 'string'],
            'field_notes.less_activities' => ['nullable', 'string'],
            'field_notes.more_activities' => ['nullable', 'string'],
            'field_notes.skills_to_explore' => ['nullable', 'string'],
            'patterns' => ['required', 'array'],
            'patterns.pattern_a' => ['required', 'string'],
            'patterns.pattern_b' => ['nullable', 'string'],
            'patterns.pattern_c' => ['nullable', 'string'],
            'research_question' => ['required', 'array'],
            'research_question.question' => ['required', 'string'],
            'pact' => ['required', 'array'],
            'pact.action' => ['required', 'string'],
            'pact.duration' => ['required', 'string'],
            'duration_value' => ['required', 'integer', 'min:1'],
            'duration_type' => ['required', Rule::in(['days', 'weeks', 'months'])],
            'start_date' => ['required', 'date'],
            'suggestion_source' => ['nullable', Rule::in(['wheel_of_life', 'manual'])],
            'related_aspect_id' => ['nullable', 'string'],
        ]);

        // Calculate end_date
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = match ($validated['duration_type']) {
            'days' => $startDate->copy()->addDays($validated['duration_value']),
            'weeks' => $startDate->copy()->addWeeks($validated['duration_value']),
            'months' => $startDate->copy()->addMonths($validated['duration_value']),
        };

        $experiment = $request->user()->tinyExperiments()->create([
            'domain_id' => $validated['domain_id'],
            'field_notes' => $validated['field_notes'],
            'patterns' => $validated['patterns'],
            'research_question' => $validated['research_question'],
            'pact' => $validated['pact'],
            'duration_value' => $validated['duration_value'],
            'duration_type' => $validated['duration_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $endDate,
            'status' => 'active',
            'suggestion_source' => $validated['suggestion_source'] ?? null,
            'related_aspect_id' => $validated['related_aspect_id'] ?? null,
        ]);

        return response()->json([
            'data' => $experiment,
        ], 201);
    }

    /**
     * Update experiment
     * PATCH /api/experiments/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $experiment = $request->user()
            ->tinyExperiments()
            ->findOrFail($id);

        $validated = $request->validate([
            'field_notes' => ['sometimes', 'array'],
            'patterns' => ['sometimes', 'array'],
            'research_question' => ['sometimes', 'array'],
            'pact' => ['sometimes', 'array'],
            'duration_value' => ['sometimes', 'integer', 'min:1'],
            'duration_type' => ['sometimes', Rule::in(['days', 'weeks', 'months'])],
            'start_date' => ['sometimes', 'date'],
        ]);

        // Recalculate end_date if duration or start_date changed
        if (isset($validated['start_date']) || isset($validated['duration_value']) || isset($validated['duration_type'])) {
            $startDate = Carbon::parse($validated['start_date'] ?? $experiment->start_date);
            $durationType = $validated['duration_type'] ?? $experiment->duration_type;
            $durationValue = $validated['duration_value'] ?? $experiment->duration_value;

            $endDate = match ($durationType) {
                'days' => $startDate->copy()->addDays($durationValue),
                'weeks' => $startDate->copy()->addWeeks($durationValue),
                'months' => $startDate->copy()->addMonths($durationValue),
            };

            $validated['end_date'] = $endDate;
        }

        $experiment->update($validated);

        return response()->json([
            'data' => $experiment->fresh(),
        ]);
    }

    /**
     * Delete experiment
     * DELETE /api/experiments/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $experiment = $request->user()
            ->tinyExperiments()
            ->findOrFail($id);

        $experiment->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Mark experiment as abandoned
     * POST /api/experiments/{id}/abandon
     */
    public function abandon(Request $request, string $id): JsonResponse
    {
        $experiment = $request->user()
            ->tinyExperiments()
            ->findOrFail($id);

        $experiment->update(['status' => 'abandoned']);

        return response()->json([
            'data' => $experiment->fresh(),
        ]);
    }

    /**
     * Mark experiment as completed
     * POST /api/experiments/{id}/complete
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $experiment = $request->user()
            ->tinyExperiments()
            ->findOrFail($id);

        $experiment->update(['status' => 'completed']);

        return response()->json([
            'data' => $experiment->fresh(),
        ]);
    }

    /**
     * Get progress stats for experiment
     * GET /api/experiments/{id}/progress
     */
    public function progress(Request $request, string $id): JsonResponse
    {
        $experiment = $request->user()
            ->tinyExperiments()
            ->findOrFail($id);

        $checkIns = $experiment->checkIns()->orderBy('date', 'desc')->get();

        $today = Carbon::today();
        $startDate = Carbon::parse($experiment->start_date);
        $endDate = Carbon::parse($experiment->end_date);

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $daysElapsed = max(0, $startDate->diffInDays($today));
        $daysRemaining = max(0, $today->diffInDays($endDate));

        $checkInsCount = $checkIns->count();
        $completedCount = $checkIns->where('completed', true)->count();
        $completionRate = $daysElapsed > 0
            ? round(($completedCount / $daysElapsed) * 100)
            : 0;

        // Calculate streaks
        $currentStreak = 0;
        $longestStreak = 0;
        $tempStreak = 0;
        $isCurrentStreakActive = false;

        // Sort by date descending (most recent first)
        $sortedCheckIns = $checkIns->sortByDesc('date');

        foreach ($sortedCheckIns as $checkIn) {
            $checkInDate = Carbon::parse($checkIn->date);

            if ($checkIn->completed) {
                $tempStreak++;
                $longestStreak = max($longestStreak, $tempStreak);

                // Current streak is only active if it includes today or yesterday
                if (!$isCurrentStreakActive && ($checkInDate->isToday() || $checkInDate->isYesterday())) {
                    $currentStreak = $tempStreak;
                    $isCurrentStreakActive = true;
                }
            } else {
                // Reset temp streak on incomplete check-in
                if (!$isCurrentStreakActive) {
                    $tempStreak = 0;
                }
            }
        }

        // Check today's status
        $todayCheckIn = $checkIns->firstWhere('date', $today->toDateString());
        $needsCheckInToday = !$todayCheckIn && $experiment->status === 'active';
        $completedToday = $todayCheckIn && $todayCheckIn->completed;

        return response()->json([
            'data' => [
                'experiment_id' => $experiment->id,
                'total_days' => $totalDays,
                'days_elapsed' => $daysElapsed,
                'days_remaining' => $daysRemaining,
                'check_ins_count' => $checkInsCount,
                'completed_count' => $completedCount,
                'completion_rate' => $completionRate,
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'needs_check_in_today' => $needsCheckInToday,
                'completed_today' => $completedToday,
            ],
        ]);
    }

    /**
     * Add check-in to experiment
     * POST /api/experiments/{id}/check-ins
     */
    public function addCheckIn(Request $request, string $id): JsonResponse
    {
        $experiment = $request->user()
            ->tinyExperiments()
            ->findOrFail($id);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'completed' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        // Check if check-in already exists for this date
        $existing = $experiment->checkIns()
            ->whereDate('date', $validated['date'])
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'Check-in already exists for this date',
                'data' => $existing,
            ], 409);
        }

        $checkIn = $experiment->checkIns()->create([
            'date' => $validated['date'],
            'completed' => $validated['completed'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'data' => $checkIn,
        ], 201);
    }

    /**
     * Get all check-ins for experiment
     * GET /api/experiments/{id}/check-ins
     */
    public function getCheckIns(Request $request, string $id): JsonResponse
    {
        $experiment = $request->user()
            ->tinyExperiments()
            ->findOrFail($id);

        $checkIns = $experiment->checkIns()
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'data' => $checkIns,
        ]);
    }

    /**
     * Get today's check-ins across all active experiments
     * GET /api/experiments/check-ins/today
     */
    public function todayCheckIns(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $checkIns = ExperimentCheckIn::whereHas('experiment', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)
                ->where('status', 'active');
        })
            ->whereDate('date', $today)
            ->with('experiment')
            ->get();

        return response()->json([
            'data' => $checkIns,
        ]);
    }
}
