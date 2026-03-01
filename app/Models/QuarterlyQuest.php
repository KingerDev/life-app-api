<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuarterlyQuest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'quarter',
        'year',
        'type',
        'discovery_answers',
        'main_goal',
        'why_important',
        'success_criteria',
        'excitement',
        'commitment',
    ];

    protected function casts(): array
    {
        return [
            'quarter' => 'integer',
            'year' => 'integer',
            'discovery_answers' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
