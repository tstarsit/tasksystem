<?php

namespace App\Imports;

use App\Models\Ticket;
use Carbon\Carbon;
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
            $excelToSystemIdMap = [4 => 1, 3 => 3, 2 => 2, 5 => 4,];
            if (!array_key_exists($excelSystemId, $excelToSystemIdMap)) {
                throw new \Exception('Invalid system_id from Excel');
            }
            $systemId = $excelToSystemIdMap[$excelSystemId];
            $status = null;
            if (!empty($deliveredDate)) {
                $status = 1;
            } elseif (!empty($acceptedDate)) {
                $status = 3;
            } elseif ($row['service_id'] == 2) {
                $status = 4;
            } else {
                $status = 2;
            }
            $isUrgent = null;

            if ($row['priority'] == 1) {
                $isUrgent = 0;
            } else {
                $isUrgent = 1;
            }

            $deletedAt = null;
            if ($row['status'] == 0) {
                $deletedAt = $this->parseDate($row['lu_date'] ?? null, 'lu_date');
            }
            $solvedByMapping = [
                5=>185,
                6=>193,
                7=>179,
                8=>178,
                9=>180,
                10=>186,
                11=>187,
                12=>188,
                13=>189,
                14=>190,
                15=>191,
                16=>192,
                17=>194,
                18=>172,
                19=>173,
                20=>174,
                22=>181,
                23=>182,
                24=>175,
                25=>183,
                26=>176,
                27=>184,
                28=>177,
                29=>171
            ];

            $solvedBy = $solvedByMapping[$row['su_id']] ?? $row['su_id'];
            // Return the Ticket model
            return new Ticket([
                'id' => (int)$row['task_id'],
                'system_id' => (int)$systemId,
                'accepted_date' => $acceptedDate,
                'solved_by' => $solvedBy,
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
        } catch (\Exception $e) {
            dd($e);
            return null; // Skip this row
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
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
            } // Handle string date (e.g., 'd/m/Y' or invalid formats like '25/11/0024')
            elseif (is_string($dateValue)) {
                // Clean the date string (e.g., fix invalid years like '0024')
                $dateValue = $this->cleanDateString($dateValue);

                // Try parsing with common formats
                $date = \DateTime::createFromFormat('d/m/Y', $dateValue);
                if (!$date) {
                    $date = \DateTime::createFromFormat('Y-m-d', $dateValue);
                }
                if (!$date) {
                    $date = \DateTime::createFromFormat('m/d/Y', $dateValue);
                }
                if (!$date) {
                    throw new \Exception("Invalid date format for $fieldName: " . json_encode($dateValue));
                }
            } else {
                throw new \Exception("Invalid date format for $fieldName: " . json_encode($dateValue));
            }

            // If the date is valid, check the year range
            if ($date && (int)$date->format('Y') >= 1000) {
                return $date->format('Y-m-d H:i:s');
            }

            // Invalid year, fallback to null
            throw new \Exception("Year out of range for $fieldName: " . $date->format('Y'));
        } catch (\Exception $e) {
            // Log the error and return null
            \Log::warning($e->getMessage());
            return null; // Use null for invalid dates
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
