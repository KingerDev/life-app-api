<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentCheckIn extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'experiment_id',
        'date',
        'completed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'completed' => 'boolean',
        ];
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(TinyExperiment::class, 'experiment_id');
    }
}
