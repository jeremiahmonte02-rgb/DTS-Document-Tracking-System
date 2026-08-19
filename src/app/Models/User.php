<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents an authenticated system user with an assigned department and role.
 */
#[Fillable(['name', 'email', 'password', 'department_id', 'role_id', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
 * The department this user belongs to.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
 * The role determining this user's access level.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
 * The documents this user uploaded into the tracking system.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_user_id');
    }

    /**
 * The routing steps this user acknowledged receipt for.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function receivedRoutes(): HasMany
    {
        return $this->hasMany(DocumentRoute::class, 'received_by_user_id');
    }

    /**
 * The lifecycle events this user performed on documents.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function documentEvents(): HasMany
    {
        return $this->hasMany(DocumentEvent::class, 'user_id');
    }

    /**
 * Determine whether this user holds the system administrator role.
 *
 * @return bool
 */
    public function isAdmin(): bool
    {
        return (int) $this->role_id === 1;
    }

    /**
 * Determine whether this user holds the auditor role.
 *
 * @return bool
 */
    public function isAuditor(): bool
    {
        return (int) $this->role_id === 3;
    }

    /**
 * Determine whether this user's role includes the given permission slug.
 *
 * @param string $slug The permission slug to check (e.g. 'documents.complete').
 * @return bool
 */
    public function hasPermission(string $slug): bool
    {
        if (!$this->relationLoaded('role') && $this->role) {
            $this->load('role.permissions');
        }

        return $this->role?->permissions?->contains('slug', $slug) ?? false;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
