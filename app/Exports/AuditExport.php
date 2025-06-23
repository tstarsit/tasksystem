<?php

namespace App\Exports;

use App\Models\Audit;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AuditExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        //

        return Audit::with('client','admin') // Assuming 'user' is the relationship method
        ->select( 'ticket_id', 'user_id', 'old_value', 'new_value', 'change_type', 'changed_column', 'created_at')
            ->get()
            ->map(function ($audit) {
                $user=User::where('id', $audit->user_id)->first();
                return [
                    'ticket_id' => $audit->ticket_id,
                    'user_name' => $user->type==1?$audit->admin->name :$audit->client->name,
                    'old_value' => $audit->old_value,
                    'new_value' => $audit->new_value,
                    'change_type' => __(Audit::Type[$audit->change_type] ?? 'Unknown'), // Get the type name
                    'changed_column' => $audit->changed_column,
                    'created_at' => $audit->created_at,
                ];
            });

    }


    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Ticket ID',
            'Name',
            'Old Value',
            'New Value',
            'Type',
            'Column Name',
            'Created At',
        ];
    }
}
