<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;

class DocumentTypePolicyController extends Controller
{
    /**
     * Resolve the routing policy for a given document type.
     */
    public function show($id): JsonResponse
    {
        $documentType = DocumentType::with('routingPolicy')->find($id);

        if (!$documentType) {
            return response()->json(['has_policy' => false], 404);
        }

        $policy = $documentType->routingPolicy;

        if (!$policy) {
            return response()->json([
                'has_policy' => false,
                'document_type_name' => $documentType->name
            ]);
        }

        $routeSteps = $policy->predefined_route ?? [];

        $hydratedRoute = collect($routeSteps)->map(function ($step) {
            $department = Department::find($step['department_id']);
            return [
                'department_id'   => (int)$step['department_id'],
                'department_name' => $department ? $department->name : 'Unknown Department',
                'sla_minutes'     => $step['sla_minutes'] ?? null,
            ];
        });

        return response()->json([
            'has_policy'         => true,
            'document_type_name' => $documentType->name,
            'is_immutable'       => (bool)$policy->is_immutable,
            'predefined_route'   => $hydratedRoute
        ]);
    }
}
