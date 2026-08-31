<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents a user role that aggregates a set of permissions.
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    /**
 * The users assigned to this role.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
 * The permissions granted to this role.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
 */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
