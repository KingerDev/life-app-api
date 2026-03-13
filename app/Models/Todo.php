<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todo extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'list_id',
        'title',
        'description',
        'due_date',
        'priority',
        'is_completed',
        'completed_at',
        'is_archived',
        'sort_order',
        'aspect_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'is_completed' => 'boolean',
            'is_archived'  => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(TodoList::class, 'list_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TodoItem::class)->orderBy('sort_order');
    }
}
