<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a document category with configurable SLAs and routing policies.
 */
class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
 * The documents registered under this document type.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
 * The routing policy configured for this document type.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
    public function routingPolicy()
    {
        return $this->hasOne(DocumentRoutingPolicy::class, 'document_type_id');
    }

    /**
 * The per-department SLA overrides configured for this document type.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function slas(): HasMany
    {
        return $this->hasMany(DepartmentDocumentSla::class, 'document_type_id');
    }
}
