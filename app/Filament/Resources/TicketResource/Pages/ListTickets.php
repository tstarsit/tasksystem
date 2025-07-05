<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Exports\UserTicketExport;
use App\Filament\Resources\TicketResource;
use App\Imports\TicketImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Maatwebsite\Excel\Facades\Excel;

class ListTickets extends ListRecords
{
    use ExposesTableToWidgets;
    protected static string $resource = TicketResource::class;
    protected $listeners = ['filterUpdate' => 'updateTableFilters'];



    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Ticket')
            ->translateLabel(),

            Actions\Action::make('Import')
                ->form([
                    FileUpload::make('file')
                        ->label('Select file to import')
                        ->disk('local') // Ensures file is uploaded to local storage
                        ->directory('temp') // Stores the file in a temporary directory
                        ->required(),
                ])
                ->visible(auth()->user()->hasRole('super admin'))
                ->label('')
                ->action(function (array $data) {
                    try {
                        if (isset($data['file'])) {

                            Excel::import(new TicketImport, $data['file']);
                        } else {
                            throw new \Exception('File upload failed.');
                        }
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Tickets have been successfully imported.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {

                        Notification::make()
                            ->title('Import Failed')
                            ->body('There was an error importing the tickets: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->icon('heroicon-o-arrow-up-tray'),

            Actions\Action::make('Export')
                ->label('')
                ->visible(auth()->user()->hasRole('super admin'))
                ->action(function () {
                    return Excel::download(new UserTicketExport, 'tickets.xlsx');
                })->icon('icon-excel')

        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
           TicketResource\Widgets\TicketStat::class
        ];
    }

    public function updateTableFilters(string $filter): void
    {
        $this->tableFilters[$filter]['isActive'] = true;
    }

    /**
     * @return string
     */


}
