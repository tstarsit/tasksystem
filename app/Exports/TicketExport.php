<?php
namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TicketExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Ticket::select('id', 'system_id', 'client_id', 'service_id', 'status', 'assigned_to', 'description', 'solution', 'recommendation', 'created_at', 'delivered_date', 'isAccepted', 'isReviewed', 'isUrgent')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'System ID',
            'Client ID',
            'Service ID',
            'Status',
            'Assigned To',
            'Description',
            'Solution',
            'Recommendation',
            'Created At',
            'Delivered Date',
            'Is Accepted',
            'Is Reviewed',
            'Is Urgent',
        ];
    }
}
