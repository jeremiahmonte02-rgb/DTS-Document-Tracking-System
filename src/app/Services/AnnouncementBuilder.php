<?php

namespace App\Services;

use App\Events\AnnouncementCreated;
use App\Models\Announcement;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        $announcement = Announcement::create([
            'action_type' => $actionType,
            'title' => $title,
            'message' => $message,
            'triggered_by_user_id' => $triggeredByUserId ?? auth()->id(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
        ]);

        try {
            event(new AnnouncementCreated($announcement));
        } catch (\Throwable $e) {
            Log::warning('Announcement broadcast failed for announcement ' . $announcement->id . ': ' . $e->getMessage());
        }

        return $announcement;
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

            if (self::isRouteArray($oldValue) || self::isRouteArray($newValue)) {
                $clauses[] = self::describeRouteChange($key, (array) $oldValue, (array) $newValue);
            } elseif (is_array($oldValue) || is_array($newValue)) {
                $clauses[] = self::labelFor($key) . ' changed';
            } else {
                $clauses[] = self::labelFor($key) . ' changed from ' . self::formatValue($oldValue)
                    . ' to ' . self::formatValue($newValue);
            }
        }

        return implode('; ', $clauses);
    }

    /**
     * Detect a routing-path step list: a non-empty list whose elements are
     * arrays carrying a department_id (the predefined_route shape).
     */
    private static function isRouteArray($value): bool
    {
        if (!is_array($value) || $value === []) {
            return false;
        }

        foreach ($value as $step) {
            if (!is_array($step) || !array_key_exists('department_id', $step)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Describe added/removed/reordered departments between two route step
     * lists. Falls back to the generic phrase when the sequences carry no
     * department-level difference this can phrase.
     */
    private static function describeRouteChange(string $key, array $old, array $new): string
    {
        $order = function ($steps) {
            usort($steps, fn ($a, $b) => ((int) ($a['route_order'] ?? 0)) <=> ((int) ($b['route_order'] ?? 0)));
            return array_values(array_map(fn ($s) => (int) ($s['department_id'] ?? 0), $steps));
        };

        $oldIds = $order($old);
        $newIds = $order($new);

        $names = Department::whereIn(
            'id',
            array_values(array_unique(array_merge($oldIds, $newIds)))
        )->pluck('name', 'id');
        $name = fn ($id) => $names[$id] ?? ('department #' . $id);

        $parts = [];
        foreach (array_values(array_diff($newIds, $oldIds)) as $addedId) {
            $step = array_search($addedId, $newIds, true) + 1;
            $parts[] = 'added ' . $name($addedId) . ' at step ' . $step;
        }
        foreach (array_values(array_diff($oldIds, $newIds)) as $removedId) {
            $parts[] = 'removed ' . $name($removedId);
        }
        if ($parts === [] && $oldIds !== $newIds) {
            $parts[] = 'reordered to ' . implode(' → ', array_map($name, $newIds));
        }
        if ($parts === []) {
            return self::labelFor($key) . ' changed';
        }

        return self::labelFor($key) . ' changed: ' . implode(', ', $parts);
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
