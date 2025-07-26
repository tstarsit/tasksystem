<?php
namespace App\Exports;

use App\Helpers\Helpers;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

class UserTicketExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithEvents
{
    protected $tickets;
    protected $clientName;
    protected $startDate;
    protected $endDate;

    public function __construct($tickets)
    {
        $this->tickets = $tickets;
        $this->clientName = $tickets->first()->client->name ?? __('N/A');
        $this->startDate = $tickets->sortBy('created_at')->first()->created_at;
        $this->endDate = $tickets->sortByDesc('created_at')->first()->created_at;
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return [
            __('#'),
            __('Client'),
            __('System'),
            __('Description'),
            __('Status'),
            __('Created At'),
            __('Delivered date'),
            __('Solution'),
        ];
    }

    public function map($ticket): array
    {
        static $number = 1;

        return [
            $number++,
            $ticket->client->name ?? __('N/A'),
            $ticket->system_name,
            $this->stripHtml($ticket->description),
            __(Helpers::getStatusText($ticket->status)),
            $ticket->created_at ? Carbon::parse($ticket->created_at)->format('Y-m-d') : '',
            $ticket->delivered_date ? Carbon::parse($ticket->delivered_date)->format('Y-m-d') : '',
            $this->stripHtml($ticket->solution),
        ];
    }

    protected function stripHtml($content)
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($content)));
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,     // #
            'B' => 20,    // Client
            'C' => 15,    // System
            'D' => 40,    // Description
            'E' => 15,    // Status
            'F' => 15,    // Created At
            'G' => 15,    // Delivered Date
            'H' => 50,    // Solution
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);

        return [
            1 => [ // Header row
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => Color::COLOR_BLACK]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ],
            'A2:H' . ($this->tickets->count() + 1) => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ],
            'D' => ['alignment' => ['wrapText' => true]],
            'H' => ['alignment' => ['wrapText' => true]]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Format dates in Y-m-d format without translation
                $startDate = $this->startDate ? Carbon::parse($this->startDate)->format('Y-m-d') : '';
                $endDate = $this->endDate ? Carbon::parse($this->endDate)->format('Y-m-d') : '';

                // Generate Arabic title with Y-m-d date format
                $arabicTitle = __('Service Report for :client from :start_date to :end_date', [
                    'client' => $this->clientName,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);

                // Add title at the top
                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', $arabicTitle);

                // Style the title
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['argb' => Color::COLOR_BLACK]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Adjust row height for title
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Move headings to row 2
                $event->sheet->getDelegate()->fromArray(
                    $this->headings(),
                    null,
                    'A2',
                    true
                );
            },
        ];
    }
}
