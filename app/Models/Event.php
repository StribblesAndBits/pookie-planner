<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'start',
        'end',
        'start_time',
        'end_time',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start' => 'date:Y-m-d',
            'end' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


