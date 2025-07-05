<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Admin;
use App\Models\Audit;
use App\Models\Client;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected ?string $maxContentWidth = 'full';

    /**
     * @return string|null
     */

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                // Other fields...
                Grid::make(2) // Create a grid with 2 columns
                ->schema([
                    Grid::make(1)
            ->schema([
                RichEditor::make('description')
                    ->disabled(function ($get) {
                        return !empty($get('accepted_date'));
                    })
                    ->toolbarButtons([
                        'bold',
                        'bulletList',
                        'italic',
                        'orderedList',
                        'redo',
                        'underline',
                        'undo',
                    ])
                    ->translateLabel(),

                Textarea::make('solution')
                    ->label('Solution')
                    ->rows(5)
                    ->cols(20)
                    ->translateLabel()
                    ->visible(fn () => auth()->user()->hasAnyRole(['Head', 'super admin', 'admin']))
                    ->disabled(fn ($get) =>
                        filled($get('delivered_date')) && $get('solved_by') !== auth()->id()
                    ),
            ])->columnSpan(1),

            Grid::make(2)
            ->schema([
                Select::make('system_id')
                    ->label(__('System'))  // Translate the label
                    ->visible(auth()->user()->type == 2)
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->type == 2 && $user->client) {
                            $clientSystemIds = json_decode($user->client->system_id, true);
                            if (empty($clientSystemIds) || !is_array($clientSystemIds)) {
                                return [];
                            }
                            return collect(Ticket::SYSTEM)
                                ->filter(fn ($value, $key) => in_array($key, $clientSystemIds))
                                ->mapWithKeys(fn ($value, $key) => [$key => __($value)]) // Translate filtered options
                                ->toArray();
                        }
                        return collect(Ticket::SYSTEM)
                            ->mapWithKeys(fn ($value, $key) => [$key => __($value)]) // Translate all options
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('service_id')
                    ->label('Service')
                    ->translateLabel()
                    ->options(collect(Ticket::SERVICES)->mapWithKeys(function ($value, $key) {
                        return [$key => __($value)];
                    })->toArray())
                    ->formatStateUsing(function ($record, $state) {

                        return __($state);
                    })
                    ->visible(auth()->user()->hasAnyRole(['Head', 'super admin', 'admin'])),

                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->disabled(function ($get) {
                        return !empty($get('accepted_date'));
                    })
                    ->options(Client::with('user')->whereHas('user', function ($query) {
                        $query->active();
                    })->pluck('name', 'user_id'))
                    ->searchable()
                    ->required(auth()->user()->type == 1)
                    ->visible(auth()->user()->type == 1)
                    ->translateLabel(),

                Select::make('assigned_to')
                    ->label('Assign To')
                    ->translateLabel()
                    ->options(Admin::where('system_id', auth()->user()->type == 1 ? auth()->user()->admin->system_id : '')->whereHas('user', fn ($query) => $query
                        ->where('status', 1)
                        ->where('type', 1))->pluck('name', 'user_id'))
                    ->visible(auth()->user()->hasAnyRole(['Head', 'super admin'])),

                DateTimePicker::make('accepted_date')
                    ->label('Accepted Date')
                    ->translateLabel()
                    ->native(false)
                    ->displayFormat('d/m/Y H:i') // How it displays to users
                    ->format('Y-m-d H:i:s')      // How it stores in database
                    ->seconds(false)
                    ->visibleOn('edit')
                    ->visible(auth()->user()->hasAnyRole(['Head', 'super admin']))
                    ->disabled(function ($get) {
                        return empty($get('accepted_date'));
                    }),
                DateTimePicker::make('delivered_date')
                    ->label('Delievered Date')
                    ->translateLabel()
                    ->native(false)
                    ->format('d-m-Y H:i')
                    ->displayFormat('d/m/Y H:i')
                    ->seconds(false)
                    ->visibleOn('edit')
                    ->visible(!auth()->user()->hasRole('client')),
                Textarea::make('recommendation')
                    ->translateLabel()
                    ->columnSpan(2)
                    ->visibleOn('edit')
                    ->disabled(auth()->user()->hasRole('Client')),


                Toggle::make('isUrgent')->translateLabel(),
            ])->columnSpan(1),

                ]),


            ]);
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
            ->action(function (Model $record){
                // Get the changed column (e.g., 'deleted_at') before deletion
                $changedColumn = 'deleted_at';
                $oldValue = $record->$changedColumn; // Old value before deletion
                $newValue = now(); // New value after deletion (timestamp)

                // Store in the audit table
                Audit::create([
                    'ticket_id' => $record->id,
                    'user_id' => auth()->id(),
                    'old_value' => $oldValue, // Capture the old value before deletion
                    'new_value' => $newValue, // The new value after deletion
                    'change_type' => 3,
                    'changed_column' => $changedColumn,
                ]);
                $record->delete();
                Notification::make()
                    ->title('Deleted')
                    ->body('Ticket has been successfully imported.')
                    ->success()
                    ->send();
            }),
            RestoreAction::make()
                ->action(function (Model $record) {
                    // Get the column that changed (typically 'deleted_at')
                    $changedColumn = 'deleted_at';
                    $oldValue = $record->$changedColumn; // The timestamp before restoration
                    $newValue = null; // After restore, deleted_at becomes null

                    // Store in the audit table
                    Audit::create([
                        'ticket_id' => $record->id,
                        'user_id' => auth()->id(),
                        'old_value' => $oldValue, // Previous deleted_at timestamp
                        'new_value' => $newValue, // Null after restore
                        'change_type' => 4, // Example: '4' represents restore
                        'changed_column' => $changedColumn,
                    ]);

                    // Restore the record
                    $record->restore();
                    Notification::make()
                        ->title('Restored')
                        ->body('Ticket has been successfully restored.')
                        ->success()
                        ->send();
                }),

        ];
    }



    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Edit Ticket');
    }
}
