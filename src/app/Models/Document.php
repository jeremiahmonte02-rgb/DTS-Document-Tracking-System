<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a tracked document lifecycle record with its routing state.
 */
class Document extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'document_number',
        'title',
        'document_type_id',
        'sender_department_id',
        'current_department_id',
        'uploaded_by_user_id',
        'status',
        'description',
        'uploaded_at',
        'completed_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
 * The document type categorizing this document.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
 * The department that originated this document.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function senderDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'sender_department_id');
    }

    /**
 * The department currently holding custody of this document.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function currentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'current_department_id');
    }

    /**
 * The user who uploaded this document into the tracking system.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
 * The ordered routing steps this document traverses.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function routes(): HasMany
    {
        return $this->hasMany(DocumentRoute::class);
    }

    /**
 * The lifecycle events recorded against this document.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function events(): HasMany
    {
        return $this->hasMany(DocumentEvent::class);
    }

    /**
 * The file attachments associated with this document.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    /**
 * Accessor returning a human-readable label for the document status code.
 *
 * @return string
 */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_transfer' => 'Pending Transfer',
            'in_transit'       => 'In Transit',
            'received'         => 'Received',
            'completed'        => 'Completed',
            'rejected'         => 'Rejected',
            default            => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    /**
 * Scope the query to documents awaiting transfer from the sender's department.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopePendingTransfer($query)
    {
        return $query->where('status', 'pending_transfer');
    }

    /**
 * Scope the query to documents currently in transit between departments.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    /**
 * Scope the query to documents acknowledged by their receiving department.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
 * Scope the query to documents whose workflow has been finalized.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @return \Illuminate\Database\Eloquent\Builder
 */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
