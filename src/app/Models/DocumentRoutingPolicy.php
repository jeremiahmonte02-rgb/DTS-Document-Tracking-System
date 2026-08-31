<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the routing policy enforced for a document type (immutable routes and SLAs).
 */
class DocumentRoutingPolicy extends Model
{
    protected $fillable = ['document_type_id', 'is_immutable', 'predefined_route'];

    protected $casts = [
        'is_immutable' => 'boolean',
        'predefined_route' => 'array',
    ];

    /**
 * The document type this routing policy applies to.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
