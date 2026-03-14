<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'weekly_wheel_enabled',
        'weekly_wheel_time',
        'deadline_enabled',
        'deadline_days_before',
        'custom_enabled',
        'custom_text',
        'custom_time',
        'custom_days',
    ];

    protected function casts(): array
    {
        return [
            'weekly_wheel_enabled' => 'boolean',
            'deadline_enabled'     => 'boolean',
            'custom_enabled'       => 'boolean',
            'custom_days'          => 'array',
        ];
    }
}
