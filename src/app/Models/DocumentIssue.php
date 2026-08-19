<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a reported issue against a document, optionally triggering a reroute.
 */
class DocumentIssue extends Model
{
    protected $table = 'document_issues';

    protected $fillable = [
        'document_id',
        'reported_by_user_id',
        'assigned_department_id',
        'description',
        'type',
        'priority',
        'status',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
 * The document the issue was reported against.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
 * The user who reported this issue.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
 * The department this issue was assigned to for resolution.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    /**
 * The user who resolved this issue.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
