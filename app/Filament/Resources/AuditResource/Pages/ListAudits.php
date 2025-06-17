<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Exports\AuditExport;
use App\Filament\Resources\AuditResource;
use App\Imports\AuditImport;
use App\Imports\TicketImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;
    protected ?string $maxContentWidth='full';



    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
            Actions\Action::make('Import')
                ->form([
                    FileUpload::make('file')
                        ->label('Select file to import')
                        ->disk('local') // Ensures file is uploaded to local storage
                        ->directory('temp') // Stores the file in a temporary directory
                        ->required(),
                ])
                ->label('Import')
                ->translateLabel()
                ->action(function (array $data) {
                    try {

                        Excel::import(new AuditImport(), $data['file']);

                        Notification::make()
                            ->title('Import Successful')
                            ->body('Audits have been successfully imported.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {

                        Notification::make()
                            ->title('Import Failed')
                            ->body('There was an error importing the tickets: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })->icon('heroicon-o-arrow-up-tray'),

            Actions\Action::make('Export')
                ->label('')
                ->visible(auth()->user()->hasRole('super admin'))
                ->action(function () {
                    return Excel::download(new AuditExport(), 'Audit.xlsx');
                })->icon('icon-excel')

        ];
    }
}
