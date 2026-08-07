<?php

namespace App\Services;

use App\Enums\ActivityCode;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(
        ActivityCode $eventCode,
        string $description,
        Model|null $loggable = null,
        array $metadata = []
    ): ActivityLog {
        $user = auth()->user();

        return ActivityLog::create([
            'user_id'       => $user?->id,
            'department_id' => $user?->department_id,
            'event_code'    => $eventCode,
            'description'   => $description,
            'loggable_type' => $loggable ? get_class($loggable) : null,
            'loggable_id'   => $loggable?->getKey(),
            'metadata'      => $metadata ?: null,
            'created_at'    => now(),
        ]);
    }
}
