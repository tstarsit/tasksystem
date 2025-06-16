<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LabTrans;
use App\Models\LabTransDtl;
use App\Models\SmsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    public function store(Request $request)
    {
        try {
            dd($request->all());
            $response = Http::withoutVerifying()->post('https://business.enjazatik.com/api/v1/send-message?token=' . $request->token, [
                'message' => $request->msg_url,
                'number' => $request->number,
            ]);

        } catch (\Exception $e) {
            dd($e);
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }

}
