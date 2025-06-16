<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Admin;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use JetBrains\PhpStorm\NoReturn;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $maxContentWidth='full';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check if the username already exists
        $username = $data['username'];
        if (User::where('username', $username)->exists()) {
            throw new \Exception('User with this username already exists.');
        }

        $data['password'] = Hash::make('12345678');
        $data['type'] = 1;


        return $data;
    }

    protected function afterCreate($user = null, array $formData = []): void
    {
        // Ensure the user object is valid
        if (!$user || !$user->id) {
            return; // Skip if the user is invalid
        }

        // Check if the admin record already exists for this user (optional)
        if (Admin::where('user_id', $user->id)->exists()) {
            return; // Skip if the admin record already exists
        }


        Admin::create([
            'user_id' => $user->id,
            'name' => $formData['name'] ?? null, // Use the 'name' from the original form data
            'system_id' =>$user->type==1 ? $formData['system_id'][0] : null, // Use the 'system_id' from the original form data
        ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Store the original form data (name and system_id) before mutating
        $originalFormData = [
            'name' => $data['name'],
            'system_id' => $data['system_id'],
        ];

        // Remove fields that don't belong in the users table
        unset($data['name'], $data['system_id']);

        // Create the user record using the mutated data
        $user = parent::handleRecordCreation($data);

        // Call the afterCreate method and pass the created user and original form data
        $this->afterCreate($user, $originalFormData);

        return $user;
    }

}
