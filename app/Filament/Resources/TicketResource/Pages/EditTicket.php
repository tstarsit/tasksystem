<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Admin;
use App\Models\User;
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
use Filament\Notifications\Actions\Action;

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
                Grid::make(2)
                    ->schema([
                        // First column
                        Grid::make(1)
                            ->schema([
                                // Description field - appears for all users but with different rules
                                RichEditor::make('description')
                                    ->required()
                                    ->disabled(function ($get) {
                                        if (auth()->user()->hasRole('Client')) {
                                            // For clients: disable if accepted_date is not empty
                                            return !empty($get('accepted_date'));
                                        } elseif (auth()->user()->hasRole('admin')) {
                                            // For admins: disable if both delivered_date and solution are not null
                                            return !empty($get('delivered_date')) && !empty($get('solution'));
                                        }
                                        // Default case (other roles): not disabled
                                        return false;
                                    })
                                    ->dehydrated()
                                    ->toolbarButtons([
                                        'bold',
                                        'bulletList',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'underline',
                                        'undo',
                                    ])
                                    ->translateLabel()
                                    ->columnSpanFull(),



                                // Recommendation field - appears for all but different rules
                                Textarea::make('recommendation')
                                    ->translateLabel()
                                    ->columnSpanFull()
                                    ->visibleOn('edit')
                                    ->disabled(function ($get) {
                                        if (auth()->user()->hasRole('Client')) {
                                            return true; // Always disabled for clients
                                        } elseif (auth()->user()->type == 1) {
                                            return !empty($get('delivered_date')) && !empty($get('solution'));
                                        }
                                        return false;
                                    })
                                    ->dehydrated(),
                                // Urgent toggle - only for clients
                                Toggle::make('isUrgent')
                                    ->translateLabel()
                                    ->disabled(function ($get) {
                                        if (auth()->user()->hasRole('Client')) {
                                            return true; // Always disabled for clients
                                        } elseif (auth()->user()->type == 1) {
                                            return !empty($get('delivered_date')) && !empty($get('solution'));
                                        }
                                        return false;
                                    })
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ])
                            ->columnSpan(1),

                        // Second column (only for non-client roles)
                        Grid::make(2)
                            ->schema([
                                // All the fields that should only appear for non-client roles
                                Select::make('service_id')
                                    ->label('Service')
                                    ->translateLabel()
                                    ->options(collect(Ticket::SERVICES)->mapWithKeys(function ($value, $key) {
                                        return [$key => __($value)];
                                    })->toArray())
                                    ->formatStateUsing(function ($record, $state) {
                                        return __($state);
                                    })
                                    ->disabled(function ($get) {
                                        if (auth()->user()->hasRole('Client')) {
                                            return true; // Always disabled for clients
                                        } elseif (auth()->user()->type == 1) {
                                            return !empty($get('delivered_date')) && !empty($get('solution'));
                                        }
                                        return false;
                                    })
                                    ->dehydrated()
                                    ->visible(fn () => auth()->user()->hasAnyRole(['Head', 'super admin', 'admin'])),

                                Select::make('client_id')
                                    ->relationship('client', 'name')
                                    ->disabled(function ($record) {
                                       return !($record->created_by == 1);
                                    })
                                    ->dehydrated()
                                    ->options(Client::with('user')->whereHas('user', function ($query) {
                                        $query->active();
                                    })->pluck('name', 'user_id'))
                                    ->searchable()
                                    ->required(auth()->user()->type == 1)
                                    ->visible(fn () => auth()->user()->type == 1)
                                    ->translateLabel(),
                                Select::make('system_id')
                                    ->label(__('System'))
                                    ->options(function () {
                                        $systems = collect(Ticket::SYSTEM)
                                            ->mapWithKeys(fn ($value, $key) => [$key => __($value)]);

                                        // Get the user's allowed system_ids (decoded if stored as JSON)
                                        $allowedSystemIds = match (auth()->user()->type) {
                                            2 => auth()->user()->client->system_id ?? [],
                                            1 => $this->record?->client?->system_id ?? [],
                                            default => [],
                                        };

                                        // Ensure $allowedSystemIds is an array (decode JSON if needed)
                                        $allowedSystemIds = is_string($allowedSystemIds)
                                            ? json_decode($allowedSystemIds, true) ?? []
                                            : (array) $allowedSystemIds;

                                        // Filter systems based on allowed IDs (empty array = show all)
                                        return $allowedSystemIds
                                            ? $systems->filter(fn ($value, $key) => in_array($key, $allowedSystemIds))
                                            : $systems;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1)

                                    // Ensure dropdown shows translated names
                                    ->getOptionLabelsUsing(function ($values) {
                                        $decodedIds = is_string($values) ? json_decode($values, true) : (array) $values;
                                        return collect($decodedIds)
                                            ->map(fn ($value) => __(Ticket::SYSTEM[$value] ?? $value))
                                            ->toArray();
                                    }),
                                Select::make('assigned_to')
                                    ->label('Assign To')
                                    ->disabled(function ($get) {
                                        if (auth()->user()->hasRole('Client')) {
                                            return true; // Always disabled for clients
                                        } elseif (auth()->user()->type == 1) {
                                            return !empty($get('delivered_date')) && !empty($get('solution'));
                                        }
                                        return false;
                                    })
                                    ->dehydrated()
                                    ->translateLabel()
                                    ->options(Admin::where('system_id', auth()->user()->type == 1 ? auth()->user()->admin->system_id : '')->whereHas('user', fn ($query) => $query
                                        ->where('status', 1)
                                        ->where('type', 1))->pluck('name', 'user_id'))
                                    ->visible(fn () => auth()->user()->hasAnyRole(['Head', 'super admin'])),





                                // Client-specific fields
                                Grid::make(2)
                                    ->schema([
                                        DateTimePicker::make('accepted_date')
                                            ->label('Accepted Date')
                                            ->translateLabel()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->format('Y-m-d')
                                            ->seconds(false)
                                            ->visibleOn('edit')
                                            ->visible(fn () => auth()->user()->hasAnyRole(['Head','admin', 'super admin']))
                                            ->dehydrated(),

                                        DateTimePicker::make('delivered_date')
                                            ->label('Delivered Date')
                                            ->translateLabel()
                                            ->native(false)
                                            ->format('d-m-Y')
                                            ->displayFormat('d/m/Y')
                                            ->seconds(false)
                                            ->visibleOn('edit')
                                            ->visible(fn () => !auth()->user()->hasRole('Client')),

                                    ])
                                    ->columns(2),
                                Textarea::make('solution')
                                    ->label('Solution')
                                    ->rows(5)
                                    ->columnSpan(2)
                                    ->cols(20)
                                    ->translateLabel()
                                    ->visible(fn () => auth()->user()->hasAnyRole(['Head', 'super admin', 'admin'])),
                            ])
                            ->columnSpan(1)
                           ,
                    ])


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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $original = $record->getOriginal();

        // Update the record with new data first
        $record->fill($data);


        // Handle special cases
        $this->handleSpecialCases($record, $data, $original);

        // Save before audit logging
        $record->save();

        // Log changes
        $this->logChangesToAudit($record, $original);
        return $record;
    }
    protected function handleSpecialCases(Model $record, array $data, array $original): void
    {

        // Service ID changed
        if ($record->isDirty('service_id')) {
            $record->accepted_date = now();
            $record->accepted_by = auth()->id();
            $record->status = 3; // In progress
        }


        if (!empty($data['solution'])) {
            if (empty($data['delivered_date'])) {
                $record->status = 3;

            } else {
                $record->status = 1;
                $record->solved_by = auth()->id();
                if (empty($record->solved_by)) {
                    $record->solution=$data['solution'];
                    $record->solved_by = auth()->id();
                }
            }
        }


        if(!auth()->user()->hasRole('Client')){
            if (is_null($data['delivered_date'])&&$data['accepted_date']){
                $record->status = 3; // In progress

            }
        }


        // Assignment changes
        if ($record->isDirty('assigned_to')) {
            $this->handleAssignmentChanges($record, $original);
        }
    }
    protected function handleSolutionAndDeliveryStatus(Model $record, array $data, array $original): void
    {


        if (!empty($data['solution'])) {
            if (empty($data['delivered_date'])) {
                // Solution exists but no delivery date - set to In Progress
                $record->status = 3;
                $record->solved_by = null;

                // Log solved_by removal if it existed
                if (!empty($original['solved_by']) && $record->solved_by === null) {
                    Audit::create([
                        'ticket_id' => $record->id,
                        'user_id' => auth()->id(),
                        'changed_column' => 'solved_by',
                        'old_value' => $original['solved_by'],
                        'new_value' => null,
                        'change_type' => 1,
                    ]);
                }

            } else {
                // Both solution and delivery date exist - set to Solved
                $record->status = 1;


                // Only set solver if not already set
                if (empty($record->solved_by)) {
                    $record->solved_by = auth()->id();
                }

                // Ensure delivered_date is set
                if (empty($record->delivered_date)) {
                    $record->delivered_date = now();
                }

                // Log solver assignment if needed
                if ($record->wasChanged('solved_by')) {
                    Audit::create([
                        'ticket_id' => $record->id,
                        'user_id' => auth()->id(),
                        'changed_column' => 'solved_by',
                        'old_value' => $original['solved_by'] ?? null,
                        'new_value' => $record->solved_by,
                        'change_type' => 1,
                    ]);
                }

                // Log auto-set delivered_date if needed
                if ($record->wasChanged('delivered_date') && !isset($data['delivered_date'])) {
                    Audit::create([
                        'ticket_id' => $record->id,
                        'user_id' => auth()->id(),
                        'changed_column' => 'delivered_date',
                        'old_value' => $original['delivered_date'] ?? null,
                        'new_value' => $record->delivered_date,
                        'change_type' => 1,
                    ]);
                }

                // Send notification if solution was changed
                if ($record->wasChanged('solution')) {
                    $this->sendSolutionNotification($record);
                }
            }
        }
    }

    protected function handleAssignmentChanges(Model $record, array $original): void
    {
        if ($record->isDirty('assigned_to')) {

            $user = User::find($record->assigned_to);
            $user->notify(
                Notification::make()
                    ->title(__('A Ticket has been assigned to you'))
                    ->icon('heroicon-o-document-text')
                    ->actions([
                        Action::make('view')
                            ->label('View Ticket')
                            ->translateLabel()
                            ->icon('heroicon-o-eye')
                            ->url(EditTicket::getUrl(['record' => $record->id]))
                            ->openUrlInNewTab(),
                    ])
                    ->toDatabase()
            );

            Audit::create([
                'ticket_id' => $record->id,
                'user_id' => auth()->id(),
                'changed_column' => 'assigned_to',
                'old_value' => $original['assigned_to'] ?? null,
                'new_value' => $record->assigned_to,
                'change_type' => 2,
            ]);
        }
    }

    protected function logChangesToAudit(Model $record, array $original): void
    {
        foreach ($record->getDirty() as $attribute => $newValue) {
            if (!array_key_exists($attribute, $original) || in_array($attribute, ['created_at', 'updated_at', 'accepted_date'])) {
                continue;
            }

            if ($original[$attribute] != $newValue) {
                Audit::create([
                    'ticket_id' => $record->id,
                    'user_id' => auth()->id(),
                    'changed_column' => $attribute,
                    'old_value' => is_array($original[$attribute])
                        ? json_encode($original[$attribute])
                        : $original[$attribute],
                    'new_value' => is_array($newValue)
                        ? json_encode($newValue)
                        : $newValue,
                    'change_type' => 1,
                ]);
            }
        }
    }

    protected function sendSolutionNotification(Model $record): void
    {
        $user = User::find($record->client_id);
        $user->notify(
            Notification::make()
                ->title('تم حل المشكلة')
                ->icon('heroicon-o-document-text')
                ->actions([
                    Action::make('view')
                        ->label('View Ticket')
                        ->translateLabel()
                        ->icon('heroicon-o-eye')
                        ->url(EditTicket::getUrl(['record' => $record->id]))
                        ->openUrlInNewTab(),
                ])
                ->toDatabase()
        );
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
