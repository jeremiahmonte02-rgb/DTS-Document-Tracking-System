<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents the per-year counter used to generate sequential tracking numbers.
 */
class DocumentNumberSequence extends Model
{
    protected $fillable = [
        'year_year',
        'last_sequence',
    ];

    protected $casts = [
        'last_sequence' => 'integer',
    ];
}
