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

#[Fillable(['name', 'email', 'password', 'department_id', 'role_id', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_user_id');
    }

    public function receivedRoutes(): HasMany
    {
        return $this->hasMany(DocumentRoute::class, 'received_by_user_id');
    }

    public function documentEvents(): HasMany
    {
        return $this->hasMany(DocumentEvent::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return (int) $this->role_id === 1;
    }

    public function isAuditor(): bool
    {
        return (int) $this->role_id === 3;
    }

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
