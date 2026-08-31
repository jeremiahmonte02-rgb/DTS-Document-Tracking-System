<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a per-department processing-time SLA override for a document type.
 */
class DepartmentDocumentSla extends Model
{
    protected $table = 'department_document_slas';

    protected $fillable = [
        'department_id',
        'document_type_id',
        'processing_time_minutes'
    ];

    /**
 * The department this SLA override applies to.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
 * The document type this SLA override is configured for.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}

