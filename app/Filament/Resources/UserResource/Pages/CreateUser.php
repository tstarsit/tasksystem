<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Admin;
use App\Models\Client;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected ?string $maxContentWidth = 'full';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check if the username already exists
        $username = $data['username'];
        if (User::where('username', $username)->exists()) {
            throw new \Exception('User with this username already exists.');
        }

        // Set default password
        $data['password'] = Hash::make('123');

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Store the original form data (name and system_id/system_ids) before mutating
        $originalFormData = [
            'name' => $data['name'],
            'type' => $data['type'],
            'system_id' => $data['system_id'] ?? null,
            'system_ids' => $data['system_ids'] ?? null,
        ];

        // Remove fields that don't belong in the users table
        unset($data['name'], $data['system_id'], $data['system_ids']);

        // Create the user record using the mutated data (includes password hashing)
        $user = parent::handleRecordCreation($data);

        // Handle Admin/Client creation based on type
        if ($originalFormData['type'] == 1) { // Admin
            if (!isset($originalFormData['system_id'])) {
                throw new \Exception('System selection is required for Admin.');
            }

            Admin::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $originalFormData['name'] ?? null,
                    'system_id' => $originalFormData['system_id'] // Single ID
                ]
            );
        } else { // Client
            if (!isset($originalFormData['system_ids']) || empty($originalFormData['system_ids'])) {
                throw new \Exception('At least one system selection is required for Client.');
            }

            Client::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $originalFormData['name'] ?? null,
                    'system_id' => json_encode($originalFormData['system_ids']) // Store as JSON
                ]
            );
        }

        return $user;
    }
}
