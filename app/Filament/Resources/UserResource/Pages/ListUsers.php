<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Imports\ClientImport;
use App\Imports\TicketImport;
use App\Imports\UsersImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    protected ?string $maxContentWidth='full';
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Import')
                ->form([
                    FileUpload::make('file')
                        ->label('Select file to import')
                        ->disk('local') // Ensures file is uploaded to local storage
                        ->directory('temp') // Stores the file in a temporary directory
                        ->required(),
                ])
                ->label('')
                ->action(function (array $data) {
                    try {
                        if (isset($data['file'])) {

                            Excel::import(new UsersImport(), $data['file']);
                        } else {
                            throw new \Exception('File upload failed.');
                        }
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Users have been successfully imported.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('There was an error importing the users: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })->icon('heroicon-o-arrow-up-tray'),
            Actions\Action::make('Import Clients')
                ->form([
                    FileUpload::make('file')
                        ->label('Select file to import')
                        ->disk('local') // Ensures file is uploaded to local storage
                        ->directory('temp') // Stores the file in a temporary directory
                        ->required(),
                ])
                ->label('Clients')
                ->action(function (array $data) {
                    try {
                        if (isset($data['file'])) {

                            Excel::import(new ClientImport(), $data['file']);
                        } else {
                            throw new \Exception('File upload failed.');
                        }
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Clients have been successfully imported.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('There was an error importing the clients: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })->icon('heroicon-o-arrow-up-tray'),
            Actions\CreateAction::make()
            ->translateLabel(),
        ];
    }
}
