<?php

namespace App\Services;

use App\Models\DepartmentDocumentSla;
use App\Models\DocumentType;

class SlaResolver
{
    /**
     * Resolve allowed processing time in minutes for a specific route step.
     *
     * Priority Hierarchy:
     * 1. Policy Step SLA (per route_order override in predefined_route JSON)
     * 2. Department-Document Type SLA override
     * 3. Document Type default processing time
     * 4. Hardcoded Fallback (1440 mins / 24h)
     */
    public static function resolve(
        int $departmentId,
        int $documentTypeId,
        ?int $routeOrder = null,
        ?array $predefinedRoute = null
    ): int {
        // Priority 1: Policy step SLA
        if ($routeOrder !== null && !empty($predefinedRoute)) {
            foreach ($predefinedRoute as $step) {
                if ((int)($step['route_order'] ?? 0) === (int)$routeOrder
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

        // Priority 4: System Fallback (24 hours)
        return 1440;
    }
}
