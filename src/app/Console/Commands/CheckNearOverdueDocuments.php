<?php

namespace App\Console\Commands;

use App\Events\NearOverdueWarningCreated;
use App\Models\DocumentRoute;
use App\Models\DocumentRoutingPolicy;
use App\Models\Notification;
use App\Services\SlaResolver;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckNearOverdueDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dts:check-near-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active document routes and generate near-overdue (75% of SLA) warnings based on SLAs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $routes = DocumentRoute::with(['document', 'document.documentType', 'department'])
            ->where('status', 'current')
            ->get();

        // Cache each document type's predefined route JSON so per-row SLA
        // resolution via SlaResolver stays accurate (policy-step priority)
        // without an N+1 policy query per active route.
        $policies = DocumentRoutingPolicy::whereIn(
            'document_type_id',
            $routes->map(fn ($route) => $route->document?->document_type_id)
                ->filter()
                ->unique()
                ->values()
                ->all()
        )->get()->keyBy('document_type_id');

        $now = Carbon::now('Asia/Manila');
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

            // Custody clock: when this department took custody of the step.
            // Legacy rows may predate received_at, so fall back to created_at.
            $custodyStart = $route->received_at ?? $route->created_at;

            if (!$custodyStart) {
                continue;
            }

            $elapsedMinutes = Carbon::parse($custodyStart)->diffInMinutes($now);

            $predefinedRoute = isset($policies[$document->document_type_id])
                ? ($policies[$document->document_type_id]->predefined_route ?? null)
                : null;

            $allowedMinutes = SlaResolver::resolve(
                (int) $route->department_id,
                (int) $document->document_type_id,
                $route->route_order ?? null,
                $predefinedRoute
            );

            if ($allowedMinutes <= 0) {
                continue;
            }

            $isPastDue = $elapsedMinutes >= $allowedMinutes;

            if ($elapsedMinutes < ($allowedMinutes * 0.75)) {
                continue;
            }

            $departmentName = $route->department->name ?? ('department #' . $route->department_id);
            $elapsedWholeMinutes = (int) floor($elapsedMinutes);
            $percentUsed = (int) floor(($elapsedWholeMinutes / $allowedMinutes) * 100);

            if (!$isPastDue) {
                // Normal case: approaching but not yet overdue. The existing
                // dts:check-overdue command independently owns the >=100% case
                // (type='overdue') and is unaffected by anything here.
                //
                // Custody-period-scoped dedup: warn once per department per
                // custody period, not once ever. The same document_routes row
                // can be reused across periods (e.g. returned for correction,
                // then received again by the same department with a fresh
                // received_at), so only a warning created after the current
                // custody start suppresses a new one.
                $alreadyWarned = Notification::where('document_id', $route->document_id)
                    ->where('department_id', $route->department_id)
                    ->where('type', 'near_overdue')
                    ->where('created_at', '>=', $custodyStart)
                    ->exists();

                if ($alreadyWarned) {
                    continue;
                }

                $remainingMinutes = max(0, $allowedMinutes - $elapsedWholeMinutes);
                $remainingLabel = $remainingMinutes === 1 ? '1 minute remaining' : "{$remainingMinutes} minutes remaining";

                Notification::broadcastToDepartment(
                    (int) $route->department_id,
                    'near_overdue',
                    'SLA Near-Overdue Warning',
                    "Document {$document->document_number} at {$departmentName} has used {$percentUsed}% of its allotted processing time ({$remainingLabel}). Please complete the handoff soon.",
                    $route->document_id
                );

                // Best-effort live nudge for bell/banner watchers in this
                // department. Must never affect the persisted notification
                // above or the command's own success/exit status.
                try {
                    event(new NearOverdueWarningCreated((int) $route->department_id));
                } catch (\Throwable $e) {
                    Log::warning('NearOverdueWarningCreated broadcast failed for document ' . $document->document_number . ': ' . $e->getMessage());
                }
            } else {
                // Safety net: the 75%-100% window was never sampled (narrower
                // than the poll interval) and the step is already past due.
                // Still fire a late near_overdue warning so the document never
                // skips silently to only the overdue command's separate notice
                // with zero prior warning — but only if no warning of any kind
                // (near_overdue OR overdue) was already sent in this custody
                // period. An already-overdue document that a prior cycle
                // already covered needs no redundant second alert.
                $alreadyWarned = Notification::where('document_id', $route->document_id)
                    ->where('department_id', $route->department_id)
                    ->whereIn('type', ['near_overdue', 'overdue'])
                    ->where('created_at', '>=', $custodyStart)
                    ->exists();

                if ($alreadyWarned) {
                    continue;
                }

                $minutesOver = max(0, $elapsedWholeMinutes - $allowedMinutes);
                $overLabel = $minutesOver === 1 ? '1 minute over' : "{$minutesOver} minutes over";

                Notification::broadcastToDepartment(
                    (int) $route->department_id,
                    'near_overdue',
                    'SLA Near-Overdue Warning (Past Due)',
                    "Document {$document->document_number} at {$departmentName} is now past its allotted processing time ({$percentUsed}% used, {$overLabel}). No earlier warning was sent during its warning window. Please complete the handoff immediately.",
                    $route->document_id
                );

                // Best-effort live nudge for bell/banner watchers in this
                // department. Must never affect the persisted notification
                // above or the command's own success/exit status.
                try {
                    event(new NearOverdueWarningCreated((int) $route->department_id));
                } catch (\Throwable $e) {
                    Log::warning('NearOverdueWarningCreated broadcast failed for document ' . $document->document_number . ': ' . $e->getMessage());
                }
            }

            $created++;
        }

        $this->info("Near-overdue check complete. {$created} near-overdue notification(s) generated.");

        return self::SUCCESS;
    }
}
