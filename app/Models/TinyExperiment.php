<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TinyExperiment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'domain_id',
        'field_notes',
        'patterns',
        'research_question',
        'pact',
        'duration_value',
        'duration_type',
        'start_date',
        'end_date',
        'status',
        'suggestion_source',
        'related_aspect_id',
    ];

    protected function casts(): array
    {
        return [
            'field_notes' => 'array',
            'patterns' => 'array',
            'research_question' => 'array',
            'pact' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_value' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(ExperimentCheckIn::class, 'experiment_id');
    }
}
