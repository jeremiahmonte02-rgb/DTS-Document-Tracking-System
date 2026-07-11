<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sentDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'sender_department_id');
    }

    public function currentDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'current_department_id');
    }

    public function documentRoutes(): HasMany
    {
        return $this->hasMany(DocumentRoute::class);
    }

    public function documentEvents(): HasMany
    {
        return $this->hasMany(DocumentEvent::class);
    }

    public function slas(): HasMany
    {
        return $this->hasMany(DepartmentDocumentSla::class, 'department_id');
    }
}
