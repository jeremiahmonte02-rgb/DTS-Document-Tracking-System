<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A system-wide notice broadcast to every active user when an auditor
 * changes a policy or settings. Read state is tracked per-user in the
 * announcement_reads table (absence of a row means unread).
 */
class Announcement extends Model
{
    protected $fillable = [
        'action_type',
        'title',
        'message',
        'triggered_by_user_id',
        'subject_type',
        'subject_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The auditor (or admin) who triggered this announcement.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
