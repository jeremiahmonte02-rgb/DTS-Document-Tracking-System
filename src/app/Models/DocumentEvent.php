<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at
            ? Carbon::parse($this->created_at)->format('M d, Y h:i A')
            : '-';
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
}
