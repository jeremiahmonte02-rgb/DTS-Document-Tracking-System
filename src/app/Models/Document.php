<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function senderDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'sender_department_id');
    }

    public function currentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'current_department_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(DocumentRoute::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DocumentEvent::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

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

    public function scopePendingTransfer($query)
    {
        return $query->where('status', 'pending_transfer');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
