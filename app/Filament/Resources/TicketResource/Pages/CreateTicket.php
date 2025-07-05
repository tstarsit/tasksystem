<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    /**
     * @return string|null
     */
    protected ?string $maxContentWidth='full';
    public function getTitle(): string|Htmlable
    {
        return __('Create Ticket');
    }
    public function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // First create the record
        $record = parent::handleRecordCreation($data);

        // Determine status based on conditions
        if (!empty($data['solution'])) {
            $record->delivered_date = now();
            $record->solved_by=auth()->id();
            $record->status = 1; // Solved
        } elseif (!empty($data['service_id'])) {
            $record->accepted_date = now();
            $record->status = 3; // Accepted
        } else {
            $record->status = 2; // Pending (default)
        }

        // Save the record if any attributes were updated
        if ($record->isDirty(['delivered_date', 'accepted_date', 'status'])) {
            $record->save();
        }

        // Then send notification if needed
        if (auth()->user()->type == 2) {
            $clientName = auth()->user()->name;
            $ticketId = $record->getKey();

            $head = \App\Models\User::role('Head')
                ->where('type', 1)
                ->whereHas('admin', function($query) use ($record) {
                    $query->where('system_id', $record->system_id);
                })
                ->first();

            if ($head) {
                $head->notify(
                    Notification::make()
                        ->title('New Ticket Created')
                        ->body("{$clientName} has created a new ticket.")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->button()
                                ->icon('heroicon-o-eye')
                                ->url(route('filament.admin.resources.tickets.edit', $ticketId))
                        ])
                        ->toDatabase()
                );
            }
        }

        // Return the created record
        return $record;
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $data['status']=2;
        if (auth()->user()->type==2) {
            $data['client_id']=auth()->id();
            $data['created_by']=2;

        }
        else{
            $data['system_id']=auth()->user()->admin->system_id;

            $data['created_by']=1;
        }
        $data['deleted_at']=null;

        return $data;
    }
}
