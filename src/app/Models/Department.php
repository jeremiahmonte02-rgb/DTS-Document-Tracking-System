<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a receiving or originating unit in the document routing network.
 */
class Department extends Model
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
 * The users assigned to this department.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
 * Documents that originated from this department as sender.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function sentDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'sender_department_id');
    }

    /**
 * Documents currently in this department's custody.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function currentDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'current_department_id');
    }

    /**
 * The routing steps assigned to this department.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function documentRoutes(): HasMany
    {
        return $this->hasMany(DocumentRoute::class);
    }

    /**
 * The lifecycle events recorded within this department.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function documentEvents(): HasMany
    {
        return $this->hasMany(DocumentEvent::class);
    }

    /**
 * The per-document-type SLA overrides configured for this department.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function slas(): HasMany
    {
        return $this->hasMany(DepartmentDocumentSla::class, 'department_id');
    }
}
