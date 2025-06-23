<?php
namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserTicketExport implements FromCollection, WithHeadings, WithMapping
{
    protected $tickets;

    public function __construct($tickets)
    {
        $this->tickets = $tickets;
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Client',
            'System',
            'Description',
            'Status',
            'Created At',
            'Delivered Date',
            'Solution',
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->id,
            $ticket->client->name ?? 'N/A',
            $ticket->system_name,
            $ticket->description,
            $this->getStatusText($ticket->status),
            $ticket->created_at,
            $ticket->delivered_date ? $ticket->delivered_date : 'N/A',
            $ticket->solution,
        ];
    }

    protected function getStatusText($status)
    {
        return [
            1 => 'Resolved',
            2 => 'Pending',
            3 => 'In Progress',
            4 => 'Paid',
        ][$status] ?? 'Unknown';
    }
}
