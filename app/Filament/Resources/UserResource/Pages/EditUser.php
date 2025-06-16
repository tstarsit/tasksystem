<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function fillForm(): void
    {
        $record = $this->getRecord();

        // Eager load the admin, client, and roles relationships to avoid null issues
        $record->load(['admin', 'client', 'roles']);

        // Handle system_id based on user type
        $systemId = $record->type == 1
            ? ($record->admin ? [$record->admin->system_id] : []) // Wrap admin system_id in an array
            : ($record->client ? json_decode($record->client->system_id, true) : []); // Decode JSON to array for client

        // Ensure system_id is always an array
        if (!is_array($systemId)) {
            $systemId = [];
        }

        // Handle name based on user type
        $name = $record->type == 1
            ? ($record->admin ? $record->admin->name : '') // Single value for admin
            : ($record->client ? $record->client->name : ''); // Single value for client

        // Ensure roles is an array (even if empty)
        $roles = $record->roles ? $record->roles->pluck('id')->toArray() : [];

        // Fill the form
        $this->form->fill([
            'name' => $name,
            'username' => $record->username,
            'system_id' => $systemId, // Always an array
            'roles' => $roles, // Use pluck to get role IDs
        ]);
    }
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Eager load the admin or client relationship to avoid null issues
        $record->load(['admin', 'client']);
        // Handle system_id based on user type
        if ($record->type == 1) {
            // User is an admin - system_id is a single value

            if ($record->admin) {

                $record->admin->update([
                    'name' => $data['name'], // Update the admin's name
                    'system_id' => $data['system_id'][0], // Single value, no JSON encoding
                ]);
            }
        } else {
            // User is a client - system_id is a JSON array
            if ($record->client) {
                $data['system_id'] = json_encode($data['system_id']); // Encode array to JSON
                $record->client->update([
                    'name' => $data['name'], // Update the client's name
                    'system_id' => $data['system_id'],
                ]);
            }
        }

        // Return the updated user record
        return $record;
    }
}
