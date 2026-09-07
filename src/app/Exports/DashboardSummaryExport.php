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

class DashboardSummaryExport implements Export, WithMultipleSheets
{
    public function __construct(protected array $stats, protected array $context = [])
    {
    }

    public function sheets(): array
    {
        return [
            new DashboardSummarySheet($this->stats, $this->context),
            new DashboardOverdueSheet($this->stats['overdueDocuments'] ?? []),
        ];
    }
}

class DashboardSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithStrictNullComparison
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
            ['DTS Dashboard Summary', ''],
            ['Month', $this->context['month'] ?? ''],
            ['Scope', $this->context['scope'] ?? ''],
            ['Generated', now('Asia/Manila')->format('Y-m-d H:i')],
            [],
            ['Metric', 'Value'],
            ['Total Documents', $stats['totalDocuments'] ?? 0],
            ['Pending Transfer', $stats['pendingDocuments'] ?? 0],
            ['In Transit', $stats['inTransitDocuments'] ?? 0],
            ['Received in Month', $stats['receivedInMonth'] ?? 0],
            ['Avg Dwell Time (hrs)', $stats['avgDwellHours'] ?? 0],
            ['Overdue', $stats['overdueCount'] ?? 0],
            ['Avg Completion (hrs)', $stats['avgCompletionHours'] ?? 0],
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

        return $rows;
    }
}

class DashboardOverdueSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStrictNullComparison
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
