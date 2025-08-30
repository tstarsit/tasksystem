<?php

namespace App\Imports;

use App\Models\Audit;
use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AuditImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

            $assignedToMapping = [
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
        try{
            $originalAssignedId = (int)$row['su_id_assigned'];
            $mappedAssignedId = $assignedToMapping[$originalAssignedId] ?? $originalAssignedId;
            $ticket = Ticket::find((int)$row['task_id']);
            if ($ticket) {
                $ticket->assigned_to = $mappedAssignedId;
                $ticket->save();
            }

            return $ticket;
        }
               catch (\Exception $e){
            dd($e->getMessage());
        }

//        try {
//            return new Audit([
//                'ticket_id' => (int)$row['task_id'],
//                'user_id' => (int)$row['su_id_appointer'],
//                'old_value' => null,
//                'new_value' => (int) $row['su_id_assigned'],
//                'change_type' => 2,
//                'changed_column'=>'assigned_to',
//                'created_at'=>\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['at_date'])
//            ]);
//        }
//        catch (\Exception $e){
//            dd($e->getMessage());
//        }

    }
}
