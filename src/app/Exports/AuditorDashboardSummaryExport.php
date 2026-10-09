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

class AuditorDashboardSummaryExport implements Export, WithMultipleSheets
{
    public function __construct(protected array $stats, protected array $context = [])
    {
    }

    public function sheets(): array
    {
        // The Overdue Documents sheet exists only when its card is among the
        // resolved keys ('overdue', or every key when nothing was selected,
        // so the default export is unchanged).
        $sheets = [
            new AuditorDocumentListsSheet($this->stats['documentLists'] ?? []),
            new AuditorDashboardSummarySheet($this->stats, $this->context),
        ];

        $selectedKeys = $this->context['selectedKeys'] ?? ['overdue'];
        if (in_array('overdue', $selectedKeys, true)) {
            $sheets[] = new AuditorDashboardOverdueSheet($this->stats['overdueDocuments'] ?? []);
        }

        return $sheets;
    }
}

class AuditorDocumentListsSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithFreezePane, WithColumnWidths, WithStrictNullComparison
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

        // Header map is read from the shared ExportCards class so the note
        // and the "Cards included" row cannot drift from the column headers.
        $cardMap = \App\Support\ExportCards::headers(\App\Support\ExportCards::DASHBOARD_AUDITOR);
        $selectedKeys = $this->context['selectedKeys'] ?? array_keys($cardMap);

        if (in_array('completed', $selectedKeys, true)) {
            $rows[] = [];
            $rows[] = ['Note', "Completed includes statuses 'received' and 'completed' and is based on the document creation month."];
        }

        $rows[] = [];
        if (!($this->context['explicitSelection'] ?? false)) {
            $rows[] = ['Cards included:', 'All cards'];
        } else {
            $names = [];
            foreach ($selectedKeys as $key) {
                $names[] = $cardMap[$key] ?? $key;
            }
            $rows[] = ['Cards included:', implode(', ', $names)];
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
