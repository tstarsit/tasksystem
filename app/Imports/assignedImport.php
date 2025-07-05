<?php

namespace App\Imports;

use App\Models\Ticket;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class assignedImport implements ToCollection, WithHeadingRow
{
    /**
     * Mapping array for assigned_to transformations
     * Format: [old_id => new_id]
     */
    private $assignedToMapping = [
        5 => 119,
        6 => 127,
        7 => 113,
        8 => 112,
        9 => 114,
        10 => 120,
        11 => 121,
        12 => 122,
        13 => 123,
        14 => 124,
        15 => 125,
        16 => 126,
        17 => 128,
        18 => 105,
        19 => 106,
        20 => 107,
        21 => 110,
        22 => 115,
        23 => 116,
        24 => 108,
        25 => 117,
        27 => 118,
        29 => 102,
        30 => 129,
        31 => 130,
        32 => 100,
        33 => 101,
        34 => 104,
        35 => 103,
    ];

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            // Skip rows without ticket_id or su_id_assigned

            if (empty($row['task_id']) || empty($row['su_id_assigned'])) {
                continue;
            }
            // Apply mapping to the assigned ID
            $mappedAssigned = $this->mapId($row['su_id_assigned'], $this->assignedToMapping);
            // Update only the assigned_to column
            Ticket::where('id', $row['task_id'])
                ->update(['assigned_to' => $mappedAssigned]);
        }
    }

    /**
     * Helper function to map IDs
     * @param mixed $originalId
     * @param array $mappingArray
     * @return mixed
     */
    private function mapId($originalId, array $mappingArray)
    {
        return $mappingArray[$originalId] ?? $originalId;
    }

    /**
     * @return int
     */
    public function headingRow(): int
    {
        return 1;
    }
}
