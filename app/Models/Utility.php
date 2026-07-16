<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Utility extends Model
{
    public const TAGS = ['essential', 'non-essential'];
    public const STATUSES = ['unpaid', 'paid'];
    public const CURRENCIES = ['dollars', 'kisses'];

    protected $fillable = [
        'user_id',
        'name',
        'tag',
        'due_date',
        'amount',
        'utility_currency',
        'status',
        'recurs_monthly',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'recurs_monthly' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
