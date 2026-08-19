<?php

namespace App\Models;

use App\Enums\ActivityCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single entry in the system-wide activity audit trail.
 */
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

    /**
 * The user who triggered this activity entry.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
 * The department within which this activity was performed.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
 * The polymorphic entity (e.g. Document, Department) this log entry refers to.
 *
 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
 */
    public function loggable()
    {
        return $this->morphTo();
    }
}
