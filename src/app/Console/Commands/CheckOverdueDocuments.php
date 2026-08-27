<?php

namespace App\Console\Commands;

use App\Models\DepartmentDocumentSla;
use App\Models\DocumentRoute;
use App\Models\Notification;
use Illuminate\Console\Command;

class CheckOverdueDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dts:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active document routes and generate overdue notifications based on SLAs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $defaultSlaMinutes = 1440;

        $routes = DocumentRoute::with(['document', 'document.documentType'])
            ->where('status', 'current')
            ->get();

        $created = 0;

        foreach ($routes as $route) {
            $document = $route->document;

            // Skip routes with no document or a finalized document.
            if (!$document) {
                continue;
            }

            if (in_array($document->status, ['completed', 'cancelled', 'rejected'], true)) {
                continue;
            }

            $slaMinutes = $defaultSlaMinutes;

            $override = DepartmentDocumentSla::where('department_id', $route->department_id)
                ->where('document_type_id', $document->document_type_id)
                ->first();

            if ($override) {
                $slaMinutes = $override->processing_time_minutes;
            } elseif ($document->documentType && !is_null($document->documentType->default_processing_time)) {
                $slaMinutes = $document->documentType->default_processing_time;
            }

            $deadline = $route->created_at->addMinutes($slaMinutes);

            if (!now()->greaterThan($deadline)) {
                continue;
            }

            $alreadyNotified = Notification::where('document_id', $route->document_id)
                ->where('department_id', $route->department_id)
                ->where('type', 'overdue')
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            Notification::broadcastToDepartment(
                $route->department_id,
                'overdue',
                'SLA Overdue',
                "Document {$document->document_number} has exceeded its allotted processing time.",
                $route->document_id
            );

            $created++;
        }

        $this->info("Overdue check complete. {$created} overdue notification(s) generated.");

        return self::SUCCESS;
    }
}
