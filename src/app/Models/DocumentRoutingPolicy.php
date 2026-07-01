<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRoutingPolicy extends Model
{
    protected $fillable = ['document_type_id', 'is_immutable', 'predefined_route'];

    protected $casts = [
        'is_immutable' => 'boolean',
        'predefined_route' => 'array',
    ];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
