<?php

namespace App\Http\Controllers;

use App\Models\LabTrans;
use App\Models\LabTransDtl;
use App\Models\SmsClient;
use App\Providers\Filament\MYPDF;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $r = $request->query('r'); // If passed as a query parameter
        if (!$r) {
            return response()->json(['error' => 'Missing r parameter'], 400);
        }

        // Search for 'r' in the 'url' column of LabTrans
        $data['LabTrans'] = LabTrans::where('url', 'like', "%r={$r}")->first();
        $data['LabTransDtls'] = LabTransDtl::where('req_no', $data['LabTrans']->request_no)
            ->orderBy('order_item') // Orders tests within each group
            ->get()
            ->groupBy('PARA1_DESC'); // Groups by PARA1_DESC

        $data['allTests']=LabTrans::where('patient_id',$data['LabTrans']->patient_id)
            ->where('sms_hos_code',$data['LabTrans']->sms_hos_code)->select('result_date','url')->get();
        $data['hospital']= SmsClient::where('code',$data['LabTrans']->sms_hos_code)->first();
        return view('welcome',$data);
    }



    public function downloadPDF($code)
    {
        if (!$code) {
            return response()->json(['error' => 'Missing r parameter'], 400);
        }

        // Fetch LabTrans data using the 'r' parameter from the URL
        $LabTrans = LabTrans::where('url', 'like', "%r={$code}")->first();
        if (!$LabTrans) {
            return response()->json(['error' => 'No record found'], 404);
        }
       $hospital= SmsClient::with('client')->where('code',$LabTrans->sms_hos_code)->first();

        $LabTransDtls = LabTransDtl::where('req_no', $LabTrans->request_no)
            ->orderBy('order_item') // Orders tests within each group
            ->get()
            ->groupBy('PARA1_DESC');

        $mpdf = new \Mpdf\Mpdf([
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 50,
            'margin_bottom' => 50
        ]);

        $mpdf->SetWatermarkText($hospital->client->name);
        $mpdf->showWatermarkText = true; // Enable text watermark
        $mpdf->watermarkTextAlpha = 0.1; // Adjust transparency (0.1 is very light, increase for darker)
        $mpdf->watermark_font = 'Arial'; // Optional: Set the font for watermark
        $mpdf->watermark_size = 50; // Optional: Adjust watermark size


        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        // Read and encode the image to base64
        $headerImagePath = public_path('assets/img/fr_header.jpg');
        $footerImagePath = public_path('assets/img').'/'.$hospital->footer;
        $headerImageSrc = file_exists($headerImagePath) ? 'data:image/jpg;base64,' . base64_encode(file_get_contents($headerImagePath)) : '';
        $footerImageSrc = file_exists($footerImagePath) ? 'data:image/jpg;base64,' . base64_encode(file_get_contents($footerImagePath)) : '';

        // Set PDF Header with Logo
        $headerHTML = '<div style="text-align: center;"><img src="' . $headerImageSrc . '" style="width: 100%; height: auto;"></div>';
        $mpdf->SetHTMLHeader($headerHTML);
        if ($footerImageSrc) {
            $footerHTML = '<div style="text-align: center;"><img src="' . $footerImageSrc . '" style="width: 100%; height: auto;"></div>';
            $mpdf->SetHTMLFooter($footerHTML);
        }

        // Define the PDF content with dynamic data
        $html = '
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: "dejavusans"; }
            .container { width: 100%; padding: 10px; }
            .section-title { text-align: center; font-size: 18px; font-weight: bold; padding: 10px; background-color: #d1d5db; }
            .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .table th, .table td { border: 1px solid black; padding: 8px; text-align: center; }
            .group-header { background-color: #b2d6fd; font-size: 16px; font-weight: bold; padding: 10px; text-align: center; }
            .data-row:hover { background-color: #f3f4f6; }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Personal Data -->
            <div class="section-title">PERSONAL DATA</div>
            <table class="table">
                <tr>
                    <th>Name</th>
                    <td>' . $LabTrans->patient_desc . '</td>
                    <th>Age</th>
                    <td>' . $LabTrans->patient_age . '</td>
                </tr>
                <tr>
                    <th>Gender</th>
                    <td>' . ($LabTrans->gender == 1 ? 'Male' : 'Female') . '</td>
                    <th>Order No.</th>
                    <td>' . $LabTrans->request_no . '</td>
                </tr>
                <tr>
                    <th>Collected</th>
                    <td>' . \Carbon\Carbon::parse($LabTrans->result_date)->format('Y-m-d') . '</td>
                    <th>Doctor Name</th>
                    <td>' . $LabTrans->emp_desc . '</td>
                </tr>
            </table>

            <!-- Examination Data -->
            <div style="margin-top: 30px;" class="section-title">EXAMINATION DATA</div>';

        foreach ($LabTransDtls as $group => $tests) {
            $html .= '<div style="margin-top: 30px;" class="group-header">' . $group . '</div>
          <table class="table" style="margin-top: 10px;">
              <tr>
                  <th>Test</th>
                  <th>Result</th>
                  <th>Reference Values</th>
              </tr>';

            foreach ($tests as $test) {
                $html .= '
                <tr class="data-row">
                    <td>' . $test->LAB_ITEM_DESC . '</td>
                    <td>' . $test->RESULT_L . ' ' . $test->UNIT_DESC . '</td>
                    <td>' . $test->NORMAL_RESULT . '</td>
                </tr>';
            }
            $html .= '</table>';
        }

        $html .= '</div></body></html>';

        // Write content to PDF and output
        $mpdf->WriteHTML($html);
        return $mpdf->Output('Lab_Report.pdf', 'I');
    }
}
