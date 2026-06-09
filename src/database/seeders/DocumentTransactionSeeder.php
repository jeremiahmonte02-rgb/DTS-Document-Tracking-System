<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $users = DB::table('users')->pluck('id')->toArray();
        $departments = DB::table('departments')->pluck('id')->toArray();

        if (empty($users) || empty($departments)) {
            $this->command->warn('Users or departments table is empty. Skipping DocumentTransactionSeeder.');
            return;
        }

        // Ensure standard Document Types exist
        foreach (['Memorandum', 'Purchase Order', 'Clearance Form', 'Official Letter'] as $typeName) {
            DB::table('document_types')->updateOrInsert(
                ['name' => $typeName],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        $typeIds = DB::table('document_types')->pluck('id')->toArray();

        $statuses = ['received', 'pending_transfer', 'in_transit', 'rejected'];
        $eventTypes = ['uploaded', 'qr_generated', 'scanned', 'received', 'rejected', 'dispatched'];

        $titles = [
            'Budget Allocation Report Q1',
            'Faculty Appointment Request',
            'IT Infrastructure Proposal',
            'Student Clearance Form',
            'Procurement Request Form',
            'Annual Performance Review',
            'Research Grant Application',
            'Facilities Maintenance Request',
            'Curriculum Revision Proposal',
            'Employee Onboarding Checklist',
            'Security Audit Report',
            'Legal Compliance Review',
            'Marketing Campaign Analysis',
            'Vendor Contract Agreement',
            'Board Meeting Minutes',
            'Training Schedule 2026',
            'Data Privacy Policy Update',
            'Project Milestone Report',
            'Customer Feedback Report',
            'Academic Evaluation File #',
        ];

        for ($i = 1; $i <= 20; $i++) {
            $status = $statuses[array_rand($statuses)];
            $userId = $users[array_rand($users)];
            $senderDeptId = $departments[array_rand($departments)];
            $currentDeptId = $departments[array_rand($departments)];

            // Ensure current department differs from sender
            while ($currentDeptId === $senderDeptId) {
                $currentDeptId = $departments[array_rand($departments)];
            }

            $docId = (string) \Illuminate\Support\Str::orderedUuid();
            $uploadedAt = Carbon::now()->subDays(rand(1, 10))->subHours(rand(1, 8));
            $completedAt = $status === 'received' ? $uploadedAt->copy()->addDays(rand(1, 3)) : null;

            DB::table('documents')->insert([
                'id' => $docId,
                'document_number' => 'DOC-' . Carbon::now()->year . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'title' => $i === 20 ? 'Academic Evaluation File #20' : $titles[$i - 1],
                'document_type_id' => $typeIds[array_rand($typeIds)],
                'sender_department_id' => $senderDeptId,
                'uploaded_by_user_id' => $userId,
                'current_department_id' => $currentDeptId,
                'status' => $status,
                'description' => 'Automated seed transaction for baseline testing and development validation.',
                'uploaded_at' => $uploadedAt,
                'completed_at' => $completedAt,
                'created_at' => $uploadedAt,
                'updated_at' => $completedAt ?? $uploadedAt->copy()->addHours(rand(1, 12)),
            ]);

            // Seed document route (single route step: sender -> current department)
            $routeStatus = match ($status) {
                'received' => 'received',
                'in_transit' => 'current',
                'rejected' => 'rejected',
                default => 'pending',
            };
            $receivedAt = $status === 'received' ? $completedAt : null;

            DB::table('document_routes')->insert([
                'document_id' => $docId,
                'department_id' => $currentDeptId,
                'route_order' => 1,
                'status' => $routeStatus,
                'received_by_user_id' => $status === 'received' ? $users[array_rand($users)] : null,
                'received_at' => $receivedAt,
                'remarks' => $status === 'rejected' ? 'Returned for revision - incomplete attachments' : null,
                'created_at' => $uploadedAt,
                'updated_at' => $uploadedAt->copy()->addHours(rand(1, 12)),
            ]);

            // Seed complementary audit trail event
            $eventLabel = match ($status) {
                'received' => 'Confirmed Receipt',
                'in_transit' => 'Document Dispatched',
                'rejected' => 'Document Rejected',
                default => 'Document Uploaded',
            };

            DB::table('document_events')->insert([
                'document_id' => $docId,
                'user_id' => $userId,
                'department_id' => $senderDeptId,
                'event_type' => $eventTypes[array_rand($eventTypes)],
                'event_label' => $eventLabel,
                'old_status' => 'pending_transfer',
                'new_status' => $status,
                'note' => 'Automated seed baseline event for development trace validation.',
                'metadata' => json_encode(['seed_source' => 'DocumentTransactionSeeder', 'iteration' => $i]),
                'created_at' => $uploadedAt->copy()->addMinutes(rand(5, 60)),
            ]);

            // For received documents, also seed a receipt scan event
            if ($status === 'received') {
                DB::table('document_events')->insert([
                    'document_id' => $docId,
                    'user_id' => $users[array_rand($users)],
                    'department_id' => $currentDeptId,
                    'event_type' => 'received',
                    'event_label' => 'Document Received via QR Scan',
                    'old_status' => 'in_transit',
                    'new_status' => 'received',
                    'note' => null,
                    'metadata' => json_encode(['scan_method' => 'qr_code']),
                    'created_at' => $completedAt,
                ]);
            }
        }

        $this->command->info('Seeded 20 transactional documents with routes and events.');
    }
}
