<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardSummaryExport implements Export, WithMultipleSheets
{
    public function __construct(protected array $stats, protected array $context = [])
    {
    }

    public function sheets(): array
    {
        return [
            new DashboardDocumentListsSheet($this->stats['documentLists'] ?? []),
            new DashboardSummarySheet($this->stats, $this->context),
            new DashboardOverdueSheet($this->stats['overdueDocuments'] ?? []),
        ];
    }
}

class DashboardDocumentListsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithFreezePane, WithColumnWidths, WithStrictNullComparison
{
    public function __construct(protected array $documentLists = [])
    {
    }

    public function title(): string
    {
        return 'Document Lists';
    }

    public function headings(): array
    {
        return array_keys($this->documentLists);
    }

    public function array(): array
    {
        $lists = array_values($this->documentLists);
        $maxRows = 0;
        foreach ($lists as $list) {
            $maxRows = max($maxRows, count($list));
        }

        // Pad shorter columns with "" (NOT null: WithStrictNullComparison
        // would render null as an empty-styled cell differently). If every
        // column is empty, $maxRows is 0 and only headers are emitted.
        $rows = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $row = [];
            foreach ($lists as $list) {
                $row[] = $list[$i] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    public function columnWidths(): array
    {
        $widths = [];
        $column = 'A';
        foreach (array_keys($this->documentLists) as $header) {
            $widths[$column] = 22;
            $column++;
        }

        return $widths;
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

        $rows[] = [];
        $rows[] = ['Note', 'Received in Month counts distinct documents with a receipt event in the selected month and may include documents created in earlier months.'];

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
