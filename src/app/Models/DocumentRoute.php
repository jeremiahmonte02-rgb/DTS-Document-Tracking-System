<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an individual routing step of a document through a department.
 */
class DocumentRoute extends Model
{
    protected $fillable = [
        'document_id',
        'department_id',
        'route_order',
        'status',
        'received_at',
        'received_by_user_id',
    ];

    protected $casts = [
        'route_order' => 'integer',
        'received_at' => 'datetime',
    ];

    /**
 * The document that this routing step belongs to.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
 * The department holding (or previously holding) custody at this routing step.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
 * The user who acknowledged receipt at this routing step.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    /**
 * Scope the query to steps awaiting their turn in the routing sequence.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopeNext($query)
    {
        return $query->where('status', 'next');
    }

    /**
 * Scope the query to steps holding active custody of the document.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopeCurrent($query)
    {
        return $query->where('status', 'current');
    }

    /**
 * Scope the query to steps queued behind the active step.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
 * Scope the query to steps that have been acknowledged by their department.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
 * Scope the query to order steps by their place in the routing sequence.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopeOrdered($query)
    {
        return $query->orderBy('route_order', 'asc');
    }
}
