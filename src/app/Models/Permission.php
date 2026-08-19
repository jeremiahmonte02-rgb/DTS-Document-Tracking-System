<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents an access permission that can be granted to roles.
 */
class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
 * The roles to which this permission has been granted.
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
 */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
