<?php

namespace App\Imports;

use App\Models\Audit;
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

        try {
            return new Audit([
                'ticket_id' => (int)$row['task_id'],
                'user_id' => (int)$row['su_id_appointer'],
                'old_value' => null,
                'new_value' => (int) $row['su_id_assigned'],
                'change_type' => 2,
                'changed_column'=>'assigned_to',
                'created_at'=>\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['at_date'])
            ]);
        }
        catch (\Exception $e){
            dd($e->getMessage());
        }

    }
}
