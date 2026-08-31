<?php

namespace App\Services;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds announcement records when an auditor (or admin) changes a policy
 * or setting. Announcements are intentionally NOT fanned out into per-user
 * rows at creation time; read state is resolved lazily via announcement_reads
 * so storage stays proportional to actual reader activity.
 */
class AnnouncementBuilder
{
    public static function create(
        string $actionType,
        string $title,
        string $message,
        ?Model $subject = null,
        ?int $triggeredByUserId = null
    ): Announcement {
        return Announcement::create([
            'action_type' => $actionType,
            'title' => $title,
            'message' => $message,
            'triggered_by_user_id' => $triggeredByUserId ?? auth()->id(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
        ]);
    }

    /**
     * Build a human-readable sentence describing the fields that changed
     * between two associative snapshots. Returns '' when nothing changed.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public static function diffToSentence(array $before, array $after): string
    {
        $clauses = [];

        foreach ($after as $key => $newValue) {
            if (!array_key_exists($key, $before)) {
                continue;
            }

            $oldValue = $before[$key];

            if (self::valuesEqual($oldValue, $newValue)) {
                continue;
            }

            if (is_array($oldValue) || is_array($newValue)) {
                $clauses[] = self::labelFor($key) . ' changed';
            } else {
                $clauses[] = self::labelFor($key) . ' changed from ' . self::formatValue($oldValue)
                    . ' to ' . self::formatValue($newValue);
            }
        }

        return implode('; ', $clauses);
    }

    private static function valuesEqual($a, $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return json_encode($a) === json_encode($b);
        }

        return $a === $b;
    }

    private static function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return 'a configured route';
        }

        if ($value === null) {
            return 'empty';
        }

        return "'" . $value . "'";
    }

    private static function labelFor(string $key): string
    {
        return match ($key) {
            'is_immutable' => 'Immutable',
            'predefined_route' => 'Route configuration',
            'name' => 'Name',
            'code' => 'Code',
            'description' => 'Description',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }
}
