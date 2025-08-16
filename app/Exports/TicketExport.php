<?php
namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use App\Helpers\Helpers;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Auth;

class TicketExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return Builder
     */
    public function query()
    {
        return $this->query->with(['client', 'solver'])
            ->select(
                'system_id',
                'client_id',
                'service_id',
                'status',
                'assigned_to',
                'description',
                'solution',
                'recommendation',
                'created_at',
                'delivered_date',
                'solved_by'
            );
    }

    /**
     * @param Ticket $ticket
     * @return array
     */
    public function map($ticket): array
    {
        static $number = 1;
        $user = Auth::user();

        // Determine service type text
        $serviceType = '';
        if ($ticket->service_id == 1) {
            $serviceType = __('Maintenance');
        } elseif ($ticket->service_id == 2) {
            $serviceType = __('Request');
        }

        $row = [
            $number++,
            $serviceType, // Service type (Maintenance/Request)
            $ticket->description,
            __(Helpers::getStatusText($ticket->status)),
            Carbon::parse($ticket->created_at)->format('d-m-Y'),
            $ticket->delivered_date ? Carbon::parse($ticket->delivered_date)->format('d-m-Y') : '',
            $ticket->solution,
            $ticket->solver->name ?? ''
        ];

        // Add client name at position 1 if user type is 1
        if ($user->type == 1) {
            array_splice($row, 1, 0, [$ticket->client->name ?? 'N/A']);
        }

        // Add system name at position 2 (or 1 if client not shown)
        array_splice($row, $user->type == 1 ? 2 : 1, 0, [__($ticket->system_name)]);

        return $row;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $user = Auth::user();
        $headings = [
            __('#'),
            __('Type'),
            __('Description'),
            __('Status'),
            __('Created At'),
            __('Delivered date'),
            __('Solution'),
            __('Solved By')
        ];

        // Add Client heading if user type is 1
        if ($user->type == 1) {
            array_splice($headings, 1, 0, [__('Client')]);
        }

        // Add System heading after Client (or at position 1 if client not shown)
        array_splice($headings, $user->type == 1 ? 2 : 1, 0, [__('System')]);

        return $headings;
    }
}
