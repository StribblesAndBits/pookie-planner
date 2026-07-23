<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JulesDay extends Model
{
    public const TYPES = ['arriving', 'leaving', 'here', 'gone'];
    public const RECURRENCE_TYPES = ['none', 'daily', 'weekly', 'biweekly', 'annually', 'custom'];
    public const CUSTOM_RECURRENCE_UNITS = ['day', 'week', 'month', 'year'];
    public const RECURRENCE_END_TYPES = ['never', 'on', 'after'];

    protected $fillable = [
        'title',
        'type',
        'start',
        'end',
        'transition_time',
        'coming_time',
        'leaving_time',
        'description',
        'all_day',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_unit',
        'recurrence_days_of_week',
        'recurrence_end_type',
        'recurrence_end_date',
        'recurrence_occurrences',
        'excluded_occurrences',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'start' => 'date:Y-m-d',
            'end' => 'date:Y-m-d',
            'all_day' => 'boolean',
            'recurrence_days_of_week' => 'array',
            'recurrence_interval' => 'integer',
            'recurrence_occurrences' => 'integer',
            'recurrence_end_date' => 'date:Y-m-d',
            'excluded_occurrences' => 'array',
        ];
    }
}
