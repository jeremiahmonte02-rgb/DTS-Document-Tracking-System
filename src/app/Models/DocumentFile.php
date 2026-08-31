<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an uploaded file attachment associated with a tracked document.
 */
class DocumentFile extends Model
{
    protected $fillable = [
        'document_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
 * The document this file belongs to.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
 * The user who uploaded this file.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
