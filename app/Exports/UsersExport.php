<?php
namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use App\Helpers\Helpers;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Auth;

class UsersExport implements FromQuery, WithHeadings, WithMapping
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

        return $this->query->with(['admin', 'client'])
            ->select(
                'id',
                'username',
                'type',


            );
    }

    /**
     * @param User $user
     * @return array
     */
    public function map($user): array
    {
        static $number = 1;

        // Determine user type label
        $userType = match($user->type) {
            1 => 'Admin',
            2 => 'Client',
            default => 'Unknown', // optional fallback
        };

        $row = [
            $number++,
            $user->username,
            $userType,  // Use the label instead of raw type number
        ];

        // Add client name at position 1 if user type is 1 or 2
        if ($user->type == 1 || $user->type == 2) {
            $name = $user->type == 1
                ? ($user->admin->name ?? 'N/A')  // Assuming admin relationship exists
                : ($user->client->name ?? 'N/A'); // Assuming client relationship exists

            // Insert name at position 1 (after the number)
            array_splice($row, 1, 0, [$name]);
        }

        return $row;
    }

    /**
     * @return array
     */
    public function headings(): array
    {

        $headings = [
            __('#'),
            __('Name'),
            __('Username'),
            __('Type'),

        ];

        return $headings;
    }
}
