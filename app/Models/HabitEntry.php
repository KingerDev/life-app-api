<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'habit_id',
        'date',
        'completed',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'completed' => 'boolean',
        ];
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
