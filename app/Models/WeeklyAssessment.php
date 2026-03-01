<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyAssessment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'week_start',
        'week_end',
        'ratings',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date:Y-m-d',
            'week_end' => 'date:Y-m-d',
            'ratings' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
