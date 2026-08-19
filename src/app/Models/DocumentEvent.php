<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a lifecycle event recorded for a document (creation, receipt, completion, etc.).
 */
class DocumentEvent extends Model
{
    protected $fillable = [
        'document_id',
        'user_id',
        'department_id',
        'event_type',
        'event_label',
        'old_status',
        'new_status',
        'note',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
 * The document this event was recorded against.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
 * The user who performed the action recorded by this event.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
 * The department within which this event occurred.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
 * Accessor returning the formatted creation timestamp as 'M d, Y h:i A'.
 *
 * @return string
 */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at
            ? Carbon::parse($this->created_at)->format('M d, Y h:i A')
            : '-';
    }

    /**
 * Scope the query to events of the given lifecycle type (e.g. 'receipt').
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @param string $type The event type code to filter by.
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
}
