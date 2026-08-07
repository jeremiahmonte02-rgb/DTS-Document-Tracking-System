<?php

namespace App\Models;

use App\Enums\ActivityCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'department_id',
        'event_code',
        'description',
        'loggable_type',
        'loggable_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'event_code' => ActivityCode::class,
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function loggable()
    {
        return $this->morphTo();
    }
}
