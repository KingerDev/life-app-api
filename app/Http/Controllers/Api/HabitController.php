<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHabitRequest;
use App\Http\Requests\UpdateHabitRequest;
use App\Http\Requests\StoreHabitEntryRequest;
use App\Http\Requests\UpdateHabitEntryRequest;
use App\Models\Habit;
use App\Models\HabitEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    private const ASPECT_COLORS = [
        'physical_health' => '#22c55e',
        'mental_health'   => '#3b82f6',
        'family_friends'  => '#f59e0b',
        'romantic_life'   => '#ec4899',
        'career'          => '#6366f1',
        'finances'        => '#10b981',
        'personal_growth' => '#8b5cf6',
        'purpose'         => '#f97316',
        'other'           => '#6b7280',
    ];

    private const ASPECT_LABELS = [
        'physical_health' => 'Fyzické zdravie',
        'mental_health'   => 'Mentálne zdravie',
        'family_friends'  => 'Rodina a priatelia',
        'romantic_life'   => 'Romantický život',
        'career'          => 'Kariéra',
        'finances'        => 'Financie',
        'personal_growth' => 'Osobný rast',
        'purpose'         => 'Zmysel života',
        'other'           => 'Iné',
    ];

    /**
     * List active habits for authenticated user
     * GET /api/habits
     */
    public function index(Request $request): JsonResponse
    {
        $habits = $request->user()
            ->habits()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $habits->items(),
            'meta' => [
                'current_page' => $habits->currentPage(),
                'last_page'    => $habits->lastPage(),
                'per_page'     => $habits->perPage(),
                'total'        => $habits->total(),
            ],
        ]);
    }

    /**
     * Create new habit
     * POST /api/habits
     */
    public function store(StoreHabitRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $habit = $request->user()->habits()->create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'aspect_id'   => $validated['aspect_id'],
            'color'       => self::ASPECT_COLORS[$validated['aspect_id']] ?? '#8b5cf6',
            'icon'        => $validated['icon'] ?? 'CalendarCheck',
            'is_active'   => true,
        ]);

        return response()->json([
            'data' => $habit,
        ], 201);
    }

    /**
     * Get single habit
     * GET /api/habits/{habit}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $habit = $request->user()
            ->habits()
            ->findOrFail($id);

        return response()->json([
            'data' => $habit,
        ]);
    }

    /**
     * Update habit
     * PATCH /api/habits/{habit}
     */
    public function update(UpdateHabitRequest $request, string $id): JsonResponse
    {
        $habit = $request->user()
            ->habits()
            ->findOrFail($id);

        $validated = $request->validated();

        // Update color if aspect_id changes
        if (isset($validated['aspect_id'])) {
            $validated['color'] = self::ASPECT_COLORS[$validated['aspect_id']] ?? $habit->color;
        }

        $habit->update($validated);

        return response()->json([
            'data' => $habit->fresh(),
        ]);
    }

    /**
     * Delete habit (cascades to entries)
     * DELETE /api/habits/{habit}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $habit = $request->user()
            ->habits()
            ->findOrFail($id);

        $habit->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Archive habit (soft disable)
     * POST /api/habits/{habit}/archive
     */
    public function archive(Request $request, string $id): JsonResponse
    {
        $habit = $request->user()
            ->habits()
            ->findOrFail($id);

        $habit->update(['is_active' => false]);

        return response()->json([
            'data' => $habit->fresh(),
        ]);
    }

    /**
     * Get all active habits with today's entry
     * GET /api/habits/today
     */
    public function today(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $habits = $request->user()
            ->habits()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $todayEntries = HabitEntry::whereIn('habit_id', $habits->pluck('id'))
            ->whereDate('date', $today)
            ->get()
            ->keyBy('habit_id');

        $data = $habits->map(function ($habit) use ($todayEntries, $today) {
            $entry = $todayEntries->get($habit->id);
            return array_merge($habit->toArray(), [
                'today_entry' => $entry,
                'aspect_label' => self::ASPECT_LABELS[$habit->aspect_id] ?? $habit->aspect_id,
            ]);
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Dashboard summary
     * GET /api/habits/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $habits = $request->user()
            ->habits()
            ->where('is_active', true)
            ->get();

        $habitIds = $habits->pluck('id');
        $totalHabits = $habits->count();

        if ($totalHabits === 0) {
            return response()->json([
                'data' => [
                    'total_habits'            => 0,
                    'completed_today'         => 0,
                    'completion_rate_today'   => 0,
                    'longest_current_streak'  => 0,
                    'habits_needing_check_in' => [],
                ],
            ]);
        }

        $todayEntries = HabitEntry::whereIn('habit_id', $habitIds)
            ->whereDate('date', $today)
            ->where('completed', true)
            ->get();

        $completedTodayIds = $todayEntries->pluck('habit_id')->toArray();
        $completedToday = count($completedTodayIds);

        // Compute streaks per habit to find the longest current streak
        $longestCurrentStreak = 0;
        foreach ($habits as $habit) {
            $streak = $this->computeCurrentStreak($habit);
            if ($streak > $longestCurrentStreak) {
                $longestCurrentStreak = $streak;
            }
        }

        return response()->json([
            'data' => [
                'total_habits'            => $totalHabits,
                'completed_today'         => $completedToday,
                'completion_rate_today'   => $totalHabits > 0
                    ? round(($completedToday / $totalHabits) * 100)
                    : 0,
                'longest_current_streak'  => $longestCurrentStreak,
                'habits_needing_check_in' => array_values(
                    array_diff($habitIds->toArray(), $completedTodayIds)
                ),
            ],
        ]);
    }

    /**
     * Per-habit stats (streaks, completion rate, weekly/monthly data)
     * GET /api/habits/{habit}/stats
     */
    public function stats(Request $request, string $id): JsonResponse
    {
        $habit = $request->user()
            ->habits()
            ->findOrFail($id);

        $today = Carbon::today();
        $allEntries = $habit->entries()->orderBy('date')->get();
        $completedEntries = $allEntries->where('completed', true);
        $completedDates = $completedEntries->pluck('date')->map(fn($d) => Carbon::parse($d)->toDateString())->flip();

        // Total days since habit was created (normalize to midnight to avoid time-of-day skew)
        $createdDate = Carbon::parse($habit->created_at)->startOfDay();
        $totalDays = max(1, $createdDate->diffInDays(Carbon::today()) + 1);
        $completedDays = $completedEntries->count();

        // Current streak
        $currentStreak = $this->computeCurrentStreak($habit, $completedDates);

        // Longest streak (O(n) pass ascending)
        $longestStreak = 0;
        $tempStreak = 0;
        $prevDate = null;

        foreach ($completedEntries->sortBy('date') as $entry) {
            $entryDate = Carbon::parse($entry->date);
            if ($prevDate !== null) {
                $diff = $prevDate->diffInDays($entryDate);
                if ($diff === 1) {
                    $tempStreak++;
                } else {
                    $longestStreak = max($longestStreak, $tempStreak);
                    $tempStreak = 1;
                }
            } else {
                $tempStreak = 1;
            }
            $prevDate = $entryDate;
        }
        $longestStreak = max($longestStreak, $tempStreak, $currentStreak);

        // Weekly completions: current Mon–Sun
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weeklyCompletions = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i)->toDateString();
            $weeklyCompletions[] = isset($completedDates[$date]) ? 1 : 0;
        }

        // Monthly completions: last 30 days as date->bool map
        $monthlyCompletions = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $monthlyCompletions[$date] = isset($completedDates[$date]);
        }

        $completedToday = isset($completedDates[$today->toDateString()]);

        return response()->json([
            'data' => [
                'habit_id'            => $habit->id,
                'total_days'          => $totalDays,
                'completed_days'      => $completedDays,
                'completion_rate'     => $totalDays > 0 ? min(100, round(($completedDays / $totalDays) * 100)) : 0,
                'current_streak'      => $currentStreak,
                'longest_streak'      => $longestStreak,
                'weekly_completions'  => $weeklyCompletions,
                'monthly_completions' => $monthlyCompletions,
                'completed_today'     => $completedToday,
            ],
        ]);
    }

    /**
     * Get entries for a habit (with optional date range)
     * GET /api/habits/{habit}/entries?from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function getEntries(Request $request, string $id): JsonResponse
    {
        $habit = $request->user()
            ->habits()
            ->findOrFail($id);

        $query = $habit->entries()->orderBy('date', 'desc');

        if ($request->has('from')) {
            $query->whereDate('date', '>=', $request->input('from'));
        }
        if ($request->has('to')) {
            $query->whereDate('date', '<=', $request->input('to'));
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    /**
     * Create or update entry for a habit (upsert by date)
     * POST /api/habits/{habit}/entries
     */
    public function storeEntry(StoreHabitEntryRequest $request, string $id): JsonResponse
    {
        $habit = $request->user()
            ->habits()
            ->findOrFail($id);

        $validated = $request->validated();

        // Upsert: update existing entry for the same date, or create new one
        $entry = $habit->entries()->where('date', $validated['date'])->first();

        if ($entry) {
            $entry->update([
                'completed' => $validated['completed'],
                'note'      => $validated['note'] ?? $entry->note,
            ]);
            $statusCode = 200;
        } else {
            $entry = $habit->entries()->create([
                'date'      => $validated['date'],
                'completed' => $validated['completed'],
                'note'      => $validated['note'] ?? null,
            ]);
            $statusCode = 201;
        }

        return response()->json([
            'data' => $entry->fresh(),
        ], $statusCode);
    }

    /**
     * Update existing entry
     * PATCH /api/habits/{habit}/entries/{entry}
     */
    public function updateEntry(UpdateHabitEntryRequest $request, string $habitId, string $entryId): JsonResponse
    {
        $habit = $request->user()
            ->habits()
            ->findOrFail($habitId);

        $entry = $habit->entries()->findOrFail($entryId);

        $entry->update($request->validated());

        return response()->json([
            'data' => $entry->fresh(),
        ]);
    }

    /**
     * Helper: compute current streak for a habit
     */
    private function computeCurrentStreak(Habit $habit, $completedDates = null): int
    {
        if ($completedDates === null) {
            $completedDates = $habit->entries()
                ->where('completed', true)
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->toDateString())
                ->flip();
        }

        $streak = 0;
        $cursor = Carbon::today();

        // If today is not completed, check if yesterday starts the streak
        if (!isset($completedDates[$cursor->toDateString()])) {
            $cursor->subDay();
        }

        while (isset($completedDates[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }
}
