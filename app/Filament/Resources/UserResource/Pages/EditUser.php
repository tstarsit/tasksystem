<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Admin;
use App\Models\Client;
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
            ? ($record->admin ? $record->admin->system_id : null) // Single value for admin
            : ($record->client ? json_decode($record->client->system_id, true) : []); // Array for client

        // Handle name based on user type
        $name = $record->type == 1
            ? ($record->admin ? $record->admin->name : '')
            : ($record->client ? $record->client->name : '');

        $roles = $record->roles ? $record->roles->pluck('id')->toArray() : [];

        // Fill the form
        $this->form->fill([
            'name' => $name,
            'username' => $record->username,
            'type' => $record->type,
            'system_id' => $record->type == 1 ? $systemId : null, // Single value for admin
            'system_ids' => $record->type == 2 ? (is_array($systemId) ? $systemId : []) : [], // Array for client
            'roles' => $roles,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Eager load the admin or client relationship to avoid null issues
        $record->load(['admin', 'client']);

        // Handle system_id based on user type
        if ($record->type == 1) {
            // User is currently an admin
            if ($data['type'] != 1) {
                // Convert admin to client
                Client::create([
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'client_type' => 1,
                    'system_id' => json_encode($data['system_ids'] ?? []),
                ]);

                Admin::where('user_id', $record->id)->delete();
                $record->type = 2;
                $record->save();
            }
        } else {
            // User is currently a client
            if ($data['type'] == 1) {
                // Convert client to admin
                Admin::create([
                    'user_id' => $record->id,
                    'name' => $record->name,
                    'system_id' => is_array($data['system_id']) ? ($data['system_id'][0] ?? null) : $data['system_id'],
                ]);

                Client::where('user_id', $record->id)->delete();
                $record->type = 1;
                $record->save();
            }
        }

        // Return the updated user record
        return $record->fresh();
    }
}
