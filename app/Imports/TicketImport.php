<?php

namespace App\Imports;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TicketImport implements ToModel, WithChunkReading, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Parse dates with validation
            $createdAt = $this->parseDate($row['cr_date'] ?? null, 'cr_date');

            $deliveredDate = $this->parseDate($row['delivered_date'] ?? null, 'delivered_date');
            $acceptedDate = $this->parseDate($row['received_date'] ?? null, 'received_date');

            $excelSystemId = $row['sys_id'];
            $excelToSystemIdMap = [4 => 1, 3 => 3, 2 => 2, 5 => 4];
            if (!array_key_exists($excelSystemId, $excelToSystemIdMap)) {
                throw new \Exception('Invalid system_id from Excel');
            }
            $systemId = $excelToSystemIdMap[$excelSystemId];

            // Determine status
            $status = !empty($deliveredDate) ? 1 :
                (!empty($acceptedDate) ? 3 :
                    ($row['service_id'] == 2 ? 4 : 2));

            $isUrgent = $row['priority'] == 1 ? 0 : 1;

            $deletedAt = $row['status'] == 0 ? $this->parseDate($row['lu_date'] ?? null, 'lu_date') : null;

            $solvedByMapping = [
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


            // Determine solved_by with mapping applied to both su_id and lu_user
            $solvedBy = null;

            if (isset($row['lu_user'])) {
                $solvedBy = $solvedByMapping[$row['lu_user']] ?? $row['lu_user'];
            } elseif (isset($row['su_id'])) {
                $solvedBy = $solvedByMapping[$row['su_id']] ?? $row['su_id'];
            }

            // Validate solved_by exists if required
            if ($solvedBy !== null && !DB::table('users')->where('id', $solvedBy)->exists()) {
                throw new \Exception("User ID {$solvedBy} does not exist in users table");
            }

            return new Ticket([
                'id' => (int)$row['task_id'],
                'system_id' => (int)$systemId,
                'accepted_date' => $acceptedDate,
                'solved_by' => $solvedBy !== null ? (int)$solvedBy : null,
                'client_id' => (int)$row['client_id'],
                'service_id' => (int)$row['service_id'],
                'status' => (int)$status,
                'description' => $row['task_details'],
                'hours' => $row['time_consumed'],
                'solution' => $row['task_solution'],
                'recommendation' => $row['task_notes'],
                'created_at' => $createdAt,
                'delivered_date' => $deliveredDate,
                'isAccepted' => (int)$row['is_tested'],
                'isReviewed' => (int)$row['is_reviewed'],
                'isUrgent' => (int)$isUrgent,
                'deleted_at' => $deletedAt
            ]);
        } catch (\Throwable $e) {
            dd($e);
            return null;
        }
    }

    /**
     * Parse and validate date values from Excel.
     *
     * @param mixed $dateValue
     * @param string $fieldName
     * @return string|null
     */
    private function parseDate($dateValue, $fieldName)
    {
        try {
            // Handle null or empty values
            if (empty($dateValue)) {
                return null;
            }

            // Handle numeric (Excel serial date)
            if (is_numeric($dateValue)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue)
                    ->format('Y-m-d H:i:s');
            }

            // Handle Arabic date strings (e.g., "21-فبراير-2023")
            if (is_string($dateValue) && preg_match('/(\d{1,2})-(.*?)-(\d{4})/', $dateValue, $matches)) {
                $day = $matches[1];
                $arabicMonth = $matches[2];
                $year = $matches[3];

                $monthMap = [
                    'يناير' => 1, 'فبراير' => 2, 'مارس' => 3, 'أبريل' => 4,
                    'مايو' => 5, 'يونيو' => 6, 'يوليو' => 7, 'أغسطس' => 8,
                    'سبتمبر' => 9, 'أكتوبر' => 10, 'نوفمبر' => 11, 'ديسمبر' => 12
                ];

                if (isset($monthMap[$arabicMonth])) {
                    $date = Carbon::create($year, $monthMap[$arabicMonth], $day);
                    return $date->format('Y-m-d H:i:s');
                }
            }

            // Handle other string formats as fallback
            if (is_string($dateValue)) {
                // Try Carbon's parse which is more flexible
                try {
                    return Carbon::parse($dateValue)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    // Try cleaning the string if parsing fails
                    $dateValue = $this->cleanDateString($dateValue);
                    return Carbon::parse($dateValue)->format('Y-m-d H:i:s');
                }
            }

            throw new \Exception("Invalid date format for $fieldName: " . json_encode($dateValue));
        } catch (\Exception $e) {
            \Log::warning("Date parsing failed for $fieldName: " . $e->getMessage());
            return null;
        }
    }
    private function cleanDateString($dateValue)
    {
        // Fix invalid years (e.g., '0024' -> '2024')
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{2})(\d{2})/', $dateValue, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = '20' . $matches[4]; // Convert '0024' to '2024'
            return "$day/$month/$year";
        }

        // Return the original value if no cleaning is needed
        return $dateValue;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
