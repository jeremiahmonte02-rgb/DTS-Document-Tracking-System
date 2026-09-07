<?php

namespace App\Services;

use App\Models\DepartmentDocumentSla;
use App\Models\DocumentType;

class SlaResolver
{
    /**
     * Fallback processing time (minutes) when neither a policy step SLA, a
     * department-document-type override, nor a document-type default applies.
     * Centralized here so the value lives in exactly one place.
     */
    public const DEFAULT_FALLBACK_MINUTES = 30;

    /**
     * Resolve allowed processing time in minutes for a specific route step.
     *
     * Priority Hierarchy:
     * 1. Policy Step SLA (matched by department_id in predefined_route JSON)
     * 2. Department-Document Type SLA override
     * 3. Document Type default processing time
     * 4. Fallback (SlaResolver::DEFAULT_FALLBACK_MINUTES, 30 mins)
     */
    public static function resolve(
        int $departmentId,
        int $documentTypeId,
        ?int $routeOrder = null,
        ?array $predefinedRoute = null
    ): int {
        // Priority 1: Policy step SLA, matched by department_id (not route_order).
        // Matching by department_id keeps the correct sla_minutes even when a Mutable
        // route is reordered or has extra departments inserted, since a step's route_order
        // can change without it being a different department. Matching by route_order alone
        // would misattribute another department's configured sla_minutes to the wrong step.
        if (!empty($predefinedRoute)) {
            foreach ($predefinedRoute as $step) {
                if ((int)($step['department_id'] ?? 0) === $departmentId
                    && isset($step['sla_minutes'])
                    && is_numeric($step['sla_minutes'])
                    && $step['sla_minutes'] > 0
                ) {
                    return (int) $step['sla_minutes'];
                }
            }
        }

        // Priority 2: Department-level SLA override
        $deptSla = DepartmentDocumentSla::where('department_id', $departmentId)
            ->where('document_type_id', $documentTypeId)
            ->first();

        if ($deptSla && !is_null($deptSla->processing_time_minutes) && $deptSla->processing_time_minutes > 0) {
            return (int) $deptSla->processing_time_minutes;
        }

        // Priority 3: Document Type default processing time
        $docType = DocumentType::find($documentTypeId);
        if ($docType && !is_null($docType->default_processing_time) && $docType->default_processing_time > 0) {
            return (int) $docType->default_processing_time;
        }

        // Priority 4: System Fallback
        return self::DEFAULT_FALLBACK_MINUTES;
    }
}
