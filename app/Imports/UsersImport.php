<?php

namespace App\Imports;

use App\Models\Admin;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class UsersImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        return DB::transaction(function () use ($row) {


            $user = User::create([
                'username' => $row['su_username'],
                'password' => Hash::make('123'),
                'type' => 1,
                'status' => 0
            ]);

            // Map system_id from Excel
            $excelSystemId = $row['sys_id'];

            $excelToSystemIdMap = [4 => 1, 3 => 3, 2 => 2, 5 => 4,6=>1];

            if (!array_key_exists($excelSystemId, $excelToSystemIdMap)) {
               throw new \Exception('Invalid system_id from Excel');
            }
            $systemId = $excelToSystemIdMap[$excelSystemId];

//            return Client::create([
//                'user_id' => $user->id,
//                'name' => trim($row['cu_f_name'] . ' ' . $row['cu_l_name']),
////                'system_id' => $systemId
//            ]);
            return Admin::create([
                'user_id' => $user->id,
                'name' => trim($row['su_f_name'] . ' ' . $row['su_l_name']),
                'system_id' => $systemId
            ]);
        });

//        return new User([
//            'id'=>$row['client_id'],
//            'username'=>$row['cu_username'],
//            'password'=>Hash::make('123'),
//            'type'=>2,
//            'status'=>0
//        ]);


    }
}
