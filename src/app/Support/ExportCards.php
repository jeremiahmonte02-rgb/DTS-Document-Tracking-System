<?php

namespace App\Support;

/**
 * Single shared map between card-selection keys, "Document Lists" export
 * column headers, and dashboard order for the Excel exports.
 *
 * Read by: Blade views (data-card-key), the controller whitelist, and the
 * export classes — so the three cannot drift apart.
 *
 * Header strings must match the 'documentLists' keys built in
 * buildDashboardStats() / buildAuditorDashboardStats() exactly.
 */
class ExportCards
{
    public const DASHBOARD_STANDARD = 'standard';

    public const DASHBOARD_AUDITOR = 'auditor';

    /**
     * Standard dashboard cards in dashboard order: stable key => column header.
     */
    public const STANDARD = [
        'total' => 'TOTAL DOCUMENT',
        'pending' => 'PENDING TRANSFER',
        'received' => 'RECEIVED IN MONTH',
        'in_transit' => 'IN-TRANSIT',
        'overdue' => 'OVERDUED',
    ];

    /**
     * Auditor dashboard cards in dashboard order: stable key => column header.
     */
    public const AUDITOR = [
        'total' => 'TOTAL DOCUMENT',
        'pending' => 'PENDING TRANSFER',
        'in_transit' => 'IN-TRANSIT',
        'completed' => 'COMPLETED',
        'rejected' => 'REJECTED',
        'overdue' => 'SLA OVERDUE',
    ];

    /**
     * Keys no card carries in the UI. They stay valid on the server (so a
     * hand-built URL can request them) but the UI can never select them.
     * 'rejected' is therefore included only when the resolved selection is
     * "all" (nothing selected) or when explicitly requested.
     */
    public const NOT_UI_SELECTABLE = ['rejected'];

    /**
     * Key => header map for a dashboard ('standard' default; anything else
     * falls back to the standard map — callers pass the constants above).
     */
    public static function headers(string $dashboard): array
    {
        return $dashboard === self::DASHBOARD_AUDITOR ? self::AUDITOR : self::STANDARD;
    }

    /**
     * Keys the UI may offer as clickable cards (dashboard order).
     */
    public static function uiSelectableKeys(string $dashboard): array
    {
        return array_values(array_diff(array_keys(self::headers($dashboard)), self::NOT_UI_SELECTABLE));
    }

    /**
     * Resolve raw request input to the keys to include, in dashboard order.
     *
     * Whitelist per dashboard, dedupe, ignore non-array input and unknown or
     * other-dashboard keys (values are trimmed and lowercased before the
     * whitelist check). If the result is empty — including when nothing was
     * sent — return ALL keys. Output can only narrow: it is always a subset
     * of the dashboard map, so scope and role access are never widened.
     */
    public static function resolve(string $dashboard, mixed $raw): array
    {
        $ordered = array_keys(self::headers($dashboard));

        if (!is_array($raw) || $raw === []) {
            return $ordered;
        }

        $wanted = [];
        foreach ($raw as $value) {
            if (!is_string($value)) {
                continue;
            }
            $key = strtolower(trim($value));
            if (in_array($key, $ordered, true) && !in_array($key, $wanted, true)) {
                $wanted[] = $key;
            }
        }

        if ($wanted === []) {
            return $ordered;
        }

        // Dashboard order, never click order.
        return array_values(array_intersect($ordered, $wanted));
    }
}
