<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

class AuditorDashboardSummaryExport implements Export, WithMultipleSheets
{
    public function __construct(protected array $stats, protected array $context = [])
    {
    }

    public function sheets(): array
    {
        return [
            new AuditorDashboardSummarySheet($this->stats, $this->context),
            new AuditorDashboardOverdueSheet($this->stats['overdueDocuments'] ?? []),
        ];
    }
}

class AuditorDashboardSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    public function __construct(protected array $stats, protected array $context = [])
    {
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function array(): array
    {
        $stats = $this->stats;

        $rows = [
            ['DTS Audit Dashboard Summary', ''],
            ['Month', $this->context['month'] ?? ''],
            ['Scope', $this->context['scope'] ?? ''],
            ['Generated', now('Asia/Manila')->format('Y-m-d H:i')],
            [],
            ['Metric', 'Value'],
            ['Total Documents', $stats['totalDocuments'] ?? 0],
            ['Pending Transfer', $stats['pendingDocuments'] ?? 0],
            ['In Transit', $stats['inTransitDocuments'] ?? 0],
            ['Completed', $stats['completedDocuments'] ?? 0],
            ['Rejected', $stats['rejectedDocuments'] ?? 0],
            ['SLA Overdue', $stats['overdueCount'] ?? 0],
            ['Avg Dwell Time (hrs)', $stats['avgDwellHours'] ?? 0],
            ['Avg Completion (hrs)', $stats['avgCompletionHours'] ?? 0],
            ['Total Issues', $stats['totalIssues'] ?? 0],
            ['Open Issues', $stats['openIssues'] ?? 0],
            ['Resolved Issues', $stats['resolvedIssues'] ?? 0],
            [],
            ['Status', 'Count'],
        ];

        foreach ($stats['statusMetrics'] ?? [] as $status => $count) {
            $rows[] = [ucwords(str_replace('_', ' ', (string) $status)), $count];
        }

        $rows[] = [];
        $rows[] = ['Department', 'Active Documents'];

        foreach ($stats['departmentDistribution'] ?? [] as $department => $count) {
            $rows[] = [$department, $count];
        }

        $rows[] = [];
        $rows[] = ['SLA Bottleneck Department', 'Overdue Steps'];

        foreach ($stats['slaBottlenecksByDept'] ?? [] as $department => $count) {
            $rows[] = [$department, $count];
        }

        $rows[] = [];
        $rows[] = ['Department', 'Reported Issues'];

        foreach ($stats['issuesByDepartment'] ?? [] as $department => $count) {
            $rows[] = [$department, $count];
        }

        return $rows;
    }
}

class AuditorDashboardOverdueSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    public function __construct(protected array $overdueDocuments = [])
    {
    }

    public function title(): string
    {
        return 'Overdue Documents';
    }

    public function headings(): array
    {
        return [
            'Document Number',
            'Title',
            'Document Type',
            'Sender Department',
            'Current Department',
            'Status',
            'Time at Current Step',
            'Uploaded Date',
        ];
    }

    public function collection(): \Illuminate\Support\Collection
    {
        return collect($this->overdueDocuments)->map(fn (array $doc) => [
            $doc['document_number'] ?? '',
            $doc['title'] ?? '',
            $doc['document_type'] ?? '',
            $doc['sender_department'] ?? '',
            $doc['current_department'] ?? '',
            $doc['status'] ?? '',
            $doc['time_at_step'] ?? '',
            $doc['uploaded_at'] ?? '',
        ]);
    }
}
