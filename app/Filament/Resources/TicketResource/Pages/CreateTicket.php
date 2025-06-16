<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
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
