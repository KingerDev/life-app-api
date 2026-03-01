<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBeliefEntryRequest;
use App\Http\Requests\UpdateBeliefReflectionRequest;
use App\Models\BeliefEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeliefController extends Controller
{
    // Mapping from Wheel of Life aspects to belief domains
    private const ASPECT_TO_DOMAIN = [
        'physical_health' => 'health',
        'mental_health' => 'confidence',
        'family_friends' => 'relationships',
        'romantic_life' => 'relationships',
        'career' => 'career',
        'finances' => 'money',
        'personal_growth' => 'learning',
        'purpose' => 'impact',
    ];

    // Domains related to quest types
    private const QUEST_DOMAINS = [
        'work' => ['career', 'money', 'impact', 'confidence'],
        'life' => ['health', 'relationships', 'creativity', 'learning', 'confidence'],
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

    public function index(Request $request): JsonResponse
    {
        $entries = $request->user()
            ->beliefEntries()
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $entries->items(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    public function store(StoreBeliefEntryRequest $request): JsonResponse
    {
        // Check if entry already exists for this date
        $existing = $request->user()
            ->beliefEntries()
            ->whereDate('date', $request->date)
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'Entry already exists for this date',
                'data' => $existing,
            ], 409);
        }

        $isCustom = $request->is_custom ||
            ($request->limiting_belief_custom && $request->liberating_belief_custom);

        $entry = $request->user()->beliefEntries()->create([
            'date' => $request->date,
            'domain' => $request->domain,
            'limiting_belief_id' => $request->limiting_belief_id,
            'liberating_belief_id' => $request->liberating_belief_id,
            'limiting_belief_custom' => $request->limiting_belief_custom,
            'liberating_belief_custom' => $request->liberating_belief_custom,
            'is_custom' => $isCustom,
            'planned_action' => $request->planned_action,
            'suggestion_source' => $request->suggestion_source,
            'related_aspect_id' => $request->related_aspect_id,
            'related_quest_id' => $request->related_quest_id,
        ]);

        return response()->json([
            'data' => $entry,
            'message' => 'Belief entry created successfully',
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $entry = $request->user()
            ->beliefEntries()
            ->findOrFail($id);

        return response()->json([
            'data' => $entry,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $entry = $request->user()
            ->beliefEntries()
            ->findOrFail($id);

        $entry->delete();

        return response()->json([
            'message' => 'Belief entry deleted successfully',
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $entry = $request->user()
            ->beliefEntries()
            ->whereDate('date', Carbon::today())
            ->first();

        return response()->json([
            'data' => $entry,
        ]);
    }

    public function updateReflection(UpdateBeliefReflectionRequest $request, string $id): JsonResponse
    {
        $entry = $request->user()
            ->beliefEntries()
            ->findOrFail($id);

        $entry->update([
            'reflection' => $request->reflection,
            'outcome_matched_prediction' => $request->outcome_matched_prediction,
        ]);

        return response()->json([
            'data' => $entry->fresh(),
            'message' => 'Reflection updated successfully',
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $user = $request->user();
        $suggestions = [];
        $addedDomains = [];

        // 1. Check Wheel of Life - find lowest aspects from current week
        $currentWeekStart = Carbon::now()->startOfWeek();
        $assessment = $user->weeklyAssessments()
            ->where('week_start', '>=', $currentWeekStart->format('Y-m-d'))
            ->first();

        if ($assessment && $assessment->ratings) {
            $ratings = collect($assessment->ratings)
                ->sortBy('value')
                ->take(3);

            foreach ($ratings as $rating) {
                $aspectId = $rating['aspectId'];
                $value = $rating['value'];
                $domain = self::ASPECT_TO_DOMAIN[$aspectId] ?? null;

                if ($domain && !in_array($domain, $addedDomains) && $value <= 6) {
                    $suggestions[] = [
                        'domain' => $domain,
                        'domainLabel' => self::DOMAIN_LABELS[$domain],
                        'source' => 'wheel_of_life',
                        'reason' => "Tvoje skóre v oblasti " . self::ASPECT_LABELS[$aspectId] . " je {$value}/10",
                        'aspectId' => $aspectId,
                        'priority' => 10 - $value, // Lower score = higher priority
                    ];
                    $addedDomains[] = $domain;
                }
            }
        }

        // 2. Check Quarterly Quests - suggest related domains
        $quarter = ceil(Carbon::now()->month / 3);
        $year = Carbon::now()->year;

        $quests = $user->quarterlyQuests()
            ->where('quarter', $quarter)
            ->where('year', $year)
            ->get();

        foreach ($quests as $quest) {
            $domains = self::QUEST_DOMAINS[$quest->type] ?? [];

            foreach ($domains as $domain) {
                if (!in_array($domain, $addedDomains)) {
                    $goalPreview = mb_strlen($quest->main_goal) > 40
                        ? mb_substr($quest->main_goal, 0, 40) . '...'
                        : $quest->main_goal;

                    $suggestions[] = [
                        'domain' => $domain,
                        'domainLabel' => self::DOMAIN_LABELS[$domain],
                        'source' => 'quest',
                        'reason' => "Môže ti pomôcť s cieľom: {$goalPreview}",
                        'questId' => $quest->id,
                        'questType' => $quest->type,
                        'priority' => 5,
                    ];
                    $addedDomains[] = $domain;
                }
            }
        }

        // Sort by priority (higher first)
        usort($suggestions, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return response()->json([
            'data' => array_slice($suggestions, 0, 5), // Return top 5 suggestions
        ]);
    }

    public function weeklyStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        // Get this week's entries
        $entries = $user->beliefEntries()
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->orderBy('date')
            ->get();

        // Count entries per domain
        $domainCounts = $entries->groupBy('domain')
            ->map(fn($group) => $group->count())
            ->sortDesc();

        // Calculate reflection stats
        $entriesWithReflection = $entries->whereNotNull('reflection');
        $totalReflections = $entriesWithReflection->count();
        $predictionsNotMatched = $entriesWithReflection
            ->where('outcome_matched_prediction', false)
            ->count();

        $predictionAccuracy = $totalReflections > 0
            ? round(($predictionsNotMatched / $totalReflections) * 100)
            : null;

        // Get evidence (reflections where prediction didn't match)
        $evidence = $entriesWithReflection
            ->where('outcome_matched_prediction', false)
            ->map(fn($entry) => [
                'date' => $entry->date->format('Y-m-d'),
                'domain' => $entry->domain,
                'domainLabel' => self::DOMAIN_LABELS[$entry->domain],
                'reflection' => $entry->reflection,
            ])
            ->values();

        return response()->json([
            'data' => [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'total_entries' => $entries->count(),
                'days_completed' => $entries->count(),
                'days_in_week' => 7,
                'domain_counts' => $domainCounts,
                'most_common_domain' => $domainCounts->keys()->first(),
                'most_common_domain_label' => $domainCounts->keys()->first()
                    ? self::DOMAIN_LABELS[$domainCounts->keys()->first()]
                    : null,
                'reflections_count' => $totalReflections,
                'prediction_not_matched_percent' => $predictionAccuracy,
                'evidence' => $evidence,
            ],
        ]);
    }
}
