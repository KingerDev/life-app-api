<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeliefEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'date',
        'domain',
        'limiting_belief_id',
        'liberating_belief_id',
        'limiting_belief_custom',
        'liberating_belief_custom',
        'is_custom',
        'planned_action',
        'reflection',
        'outcome_matched_prediction',
        'suggestion_source',
        'related_aspect_id',
        'related_quest_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'outcome_matched_prediction' => 'boolean',
            'is_custom' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedQuest(): BelongsTo
    {
        return $this->belongsTo(QuarterlyQuest::class, 'related_quest_id');
    }
}
