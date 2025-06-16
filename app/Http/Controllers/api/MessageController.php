<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LabTrans;
use App\Models\LabTransDtl;
use App\Models\SmsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        try {
            $jsonPayload = $request->json()->all();
            $labTransData = $jsonPayload['LabTrans'] ?? [];
            $testDtlData = $jsonPayload['TestDtl'] ?? [];

            // Validate required fields
            if (!isset($labTransData['REQ_NO'], $labTransData['SMS_HOS_CODE'])) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Extract values
            $smsHosCode = $labTransData['SMS_HOS_CODE'];
            $reqNo = $labTransData['REQ_NO'];

            // 🔍 Check if LabTrans record exists and delete
            LabTransDtl::where('sms_hos_code', $smsHosCode)->where('req_no', $reqNo)->delete();
            LabTrans::where('sms_hos_code', $smsHosCode)->where('request_no', $reqNo)->delete();

            // Decode EMP_DESC and PAT_DESC fields
            $decodedEmpDesc = hex2bin(str_replace(' ', '', $labTransData['EMP_DESC'] ?? ''));
            $decodedPatDesc = hex2bin(str_replace(' ', '', $labTransData['PAT_DESC'] ?? ''));
            $arabicEmpDesc = mb_convert_encoding($decodedEmpDesc, 'UTF-8', 'UTF-8');
            $arabicPatDesc = mb_convert_encoding($decodedPatDesc, 'UTF-8', 'UTF-8');

            // 🔹 Find SMS Client Data
            $urlData = SmsClient::where('code', $smsHosCode)->first();
            if (!$urlData) {
                return response()->json(['error' => 'Invalid SMS_HOS_CODE'], 400);
            }

            // 🔹 Generate Unique 15-character Hash for URL
            $uniqueId = substr(md5(uniqid()), 0, 15);

            // 🔹 Construct SMS Message
            $messageText = $urlData->prefix;
//            $phoneNumber = $labTransData['MOBILE'] ?? '000000000'; // Fallback
            $phoneNumber = 775569352; // Fallback
            $message = "{$messageText}\n\nhttps://technology.yagsite.com/view?r={$uniqueId}";

            // 🔹 Build the full request URL
            $payload = [
                'userName' => $urlData->user,
                'pass' => $urlData->password,
                'massage' => $message,
                'number' => $phoneNumber
            ];
            $fullUrl = "http://185.11.8.51/engazsms/?" . http_build_query($payload, '', '&', PHP_QUERY_RFC3986);

            // 🔹 Insert into LabTrans
            LabTrans::create([
                'seq' => $labTransData['SEQ'] ?? null,
                'item_id' => $labTransData['ITEM_ID'] ?? null,
                'request_no' => $reqNo,
                'request_date' => $labTransData['REQ_DATE'] ?? null,
                'result_date' => $labTransData['RES_DATE'] ?? null,
                'emp_desc' => $arabicEmpDesc ?? null,
                'patient_id' => $labTransData['PAT_ID'] ?? null,
                'patient_desc' => $arabicPatDesc ?? null,
                'mobile' => $labTransData['MOBILE'] ?? null,
                'visit_no' => $labTransData['VISIT_NO'] ?? null,
                'visit_seq' => $labTransData['VISIT_SEQ'] ?? null,
                'birth_date' => $labTransData['BIRTH_DATE'] ?? null,
                'gender' => $labTransData['GENDER'] ?? null,
                'url' => "http://tstars.it/h/?r={$uniqueId}",
                'sent_url' => $fullUrl,
                'patient_age' => $labTransData['PAT_AGE'] ?? null,
                'sms_hos_code' => $smsHosCode
            ]);

            // 🔹 Insert into LabTransDtl (Bulk Insert for Efficiency)
            $labTransDtlRecords = [];
            foreach ($testDtlData as $testDtl) {
                $labTransDtlRecords[] = [
                    'SEQ'            => $testDtl['SEQ'],
                    'ITEM_ID'        => $testDtl['ITEM_ID'],
                    'REQ_NO'         => $reqNo,
                    'PARA1'          => $testDtl['PARA1'],
                    'PARA1_DESC'     => $testDtl['PARA1_DESC'],
                    'PARA2'          => $testDtl['PARA2'],
                    'PARA2_DESC'     => $testDtl['PARA2_DESC'],
                    'LAB_ITEM_ID'    => $testDtl['LAB_ITEM_ID'],
                    'RESULT_L'       => $testDtl['RESULT_L'],
                    'TXT_RESULT'     => $testDtl['TXT_RESULT'],
                    'FACTOR'         => $testDtl['FACTOR'],
                    'NO1'            => $testDtl['NO1'],
                    'NO2'            => $testDtl['NO2'],
                    'FLAG'           => $testDtl['FLAG'],
                    'UNIT_ID'        => $testDtl['UNIT_ID'],
                    'UNIT_DESC'      => $testDtl['UNIT_DESC'],
                    'ORDER_ITEM'     => $testDtl['ORDER_ITEM'],
                    'DEFAULT_RESULT' => $testDtl['DEFAULT_RESULT'],
                    'LAB_ITEM_DESC'  => $testDtl['LAB_ITEM_DESC'],
                    'SMS_HOS_CODE'   => $smsHosCode,
                    'NORMAL_RESULT'  => $testDtl['NORMAL_RESULT'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }

            if (!empty($labTransDtlRecords)) {
                LabTransDtl::insert($labTransDtlRecords); // 🚀 Bulk Insert for Performance

            }

            $response = Http::asForm()->post("http://185.11.8.51/engazsms/", $payload);
             $response->body();
            // ✅ Success Response
            return response()->json([
                'message' => 'Data inserted successfully!',
                'sent_url' => $fullUrl,
            ], 201);

        } catch (\Exception $e) {
            dd($e);
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }


    public function update(Request $request)
    {
        try {
            $jsonPayload = $request->json()->all();
            $labTransData = $jsonPayload['LabTrans'] ?? [];
            $testDtlData = $jsonPayload['TestDtl'] ?? [];

            // Validate required fields
            if (!isset($labTransData['REQ_NO'], $labTransData['SMS_HOS_CODE'])) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Extract values
            $smsHosCode = $labTransData['SMS_HOS_CODE'];
            $reqNo = $labTransData['REQ_NO'];

            // 🔍 Check if LabTransDtl record exists and delete
            LabTransDtl::where('sms_hos_code', $smsHosCode)->where('req_no', $reqNo)->delete();

            // 🔹 Insert into LabTransDtl (Bulk Insert for Efficiency)
            $labTransDtlRecords = [];
            foreach ($testDtlData as $testDtl) {
                $labTransDtlRecords[] = [
                    'SEQ'            => $testDtl['SEQ'],
                    'ITEM_ID'        => $testDtl['ITEM_ID'],
                    'REQ_NO'         => $reqNo,
                    'PARA1'          => $testDtl['PARA1'],
                    'PARA1_DESC'     => $testDtl['PARA1_DESC'],
                    'PARA2'          => $testDtl['PARA2'],
                    'PARA2_DESC'     => $testDtl['PARA2_DESC'],
                    'LAB_ITEM_ID'    => $testDtl['LAB_ITEM_ID'],
                    'RESULT_L'       => $testDtl['RESULT_L'],
                    'TXT_RESULT'     => $testDtl['TXT_RESULT'],
                    'FACTOR'         => $testDtl['FACTOR'],
                    'NO1'            => $testDtl['NO1'],
                    'NO2'            => $testDtl['NO2'],
                    'FLAG'           => $testDtl['FLAG'],
                    'UNIT_ID'        => $testDtl['UNIT_ID'],
                    'UNIT_DESC'      => $testDtl['UNIT_DESC'],
                    'ORDER_ITEM'     => $testDtl['ORDER_ITEM'],
                    'DEFAULT_RESULT' => $testDtl['DEFAULT_RESULT'],
                    'LAB_ITEM_DESC'  => $testDtl['LAB_ITEM_DESC'],
                    'SMS_HOS_CODE'   => $smsHosCode,
                    'NORMAL_RESULT'  => $testDtl['NORMAL_RESULT'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }

            if (!empty($labTransDtlRecords)) {
                LabTransDtl::insert($labTransDtlRecords); // 🚀 Bulk Insert for Performance
            }

            return response()->json(['message' => 'updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }
    public function view(Request $request)
    {
        // Extract the 'r' parameter from the request
        $r = $request->query('r'); // If passed as a query parameter
        if (!$r) {
            return response()->json(['error' => 'Missing r parameter'], 400);
        }

        // Search for 'r' in the 'url' column of LabTrans
        $record = LabTrans::where('url', 'like', "%r={$r}")->first();
        dd($record);

        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        return response()->json($record);
    }
}
