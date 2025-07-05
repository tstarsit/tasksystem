<?php

namespace App\Imports;

use App\Helpers\Helpers;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;

class ClientImport implements ToCollection, WithHeadingRow
{
    protected $excelClientIds = [];

    public function collection(Collection $rows): void
    {
        // Store all client_ids from Excel
        $this->excelClientIds = $rows->pluck('client_id')->filter()->toArray();

        // Process each row from Excel
        foreach ($rows as $row) {
            $this->processRow($row);
        }

        // Process missing clients
        $missingClientIds = $this->getMissingClientIds();
        if (!empty($missingClientIds)) {
            $this->processMissingClients($missingClientIds);
        }
    }

    protected function processRow(Collection $row): void
    {
        try {
            // Update existing users' usernames
            $this->updateUsername((array)$row);

            // OR create new users and clients (uncomment one)
            // $this->createNewClient($row);
        } catch (\Exception $e) {
            Log::error('Error processing row', [
                'row' => $row,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getMissingClientIds(): array
    {
        try {
            return Client::whereNotIn('user_id', $this->excelClientIds)
                ->pluck('user_id')
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error finding missing client IDs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    protected function processMissingClients(array $missingClientIds): void
    {
        $missingClients = Client::whereIn('user_id', $missingClientIds)
            ->with('user')
            ->get();

        foreach ($missingClients as $client) {
            try {
                if ($client->user) {
                    $username = Helpers::generateUsername($client->name);
                    $client->user->update(['username' => $username]);
                }
            } catch (\Exception $e) {
                dd($e,$client);
                Log::error('Error processing missing client', [
                    'client_id' => $client->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function createNewClient(array $row): ?Model
    {
        $user = User::create([
            'id' => $row['client_id'],
            'username' => Helpers::generateUsername($row['client_a_name']),
            'password' => Hash::make('123'),
            'type' => 2,
            'status' => 1,
            'approved_at' => now(),
        ]);

        return new Client([
            'user_id' => $user->id,
            'name' => $row['client_a_name'],
            'client_type' => $row['client_type_id'],
        ]);
    }

    public function updateUsername(array $row): ?Model
    {
        if (empty($row['client_id'])) {
            Log::warning('Missing client_id in row', ['row' => $row]);
            return null;
        }

        $client = Client::with('user')->where('user_id', $row['client_id'])->first();

        if (!$client) {
            Log::warning('Client not found', ['client_id' => $row['client_id']]);
            return null;
        }

        if (!$client->user) {
            Log::warning('User not found for client', ['client_id' => $row['client_id']]);
            return null;
        }

        $username = !empty($row['cu_username'])
            ? $row['cu_username']
            : Helpers::generateUsername($client->name);

        $client->user->update(['username' => $username]);

        return $client;
    }
}
