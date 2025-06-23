<?php

namespace App\Filament\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Filament\Resources\TicketResource\Pages;
use App\Models\Admin;
use App\Models\Client;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Ticket;
use App\Providers\Filament\MYPDF;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;

use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use JetBrains\PhpStorm\NoReturn;
use PDF;
use TCPDF;
use TCPDF_FONTS;


class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'icon-ticket';


    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        // Check roles in order of highest privilege first
        if ($user->hasRole('super admin')) {
            return Ticket::withTrashed()->where('status', 2)->count();
        }

        if ($user->hasRole('Client')) {
            return Ticket::where('client_id', $user->id)->where('status', 2)->count();
        }

        if ($user->hasRole('admin') || $user->hasRole('Head')) {
            return Ticket::withTrashed()
                ->where('system_id', $user->admin->system_id)
                ->where('status', 2)
                ->count();
        }

        return null; // No badge for other roles
    }

public static function getNavigationLabel(): string
{
    return __('Tickets');
}
    public static function getPluralLabel(): ?string
    {
        return  __('Tickets');
    }


    public static function form(Form $form): Form
    {
        $isClient = auth()->user()->hasRole('Client');

        return $form
            ->schema([
                Grid::make($isClient ? 1 : 2)
                ->schema([
                    // Left Column (50%)
                    Grid::make(1) // Single column for description and solution
                    ->schema([
                        RichEditor::make('description')
                            ->disabled(function ($get) {
                                return !empty($get('accepted_date'));
                            })
                            ->toolbarButtons([
                                'bold', 'bulletList', 'italic',
                                'orderedList', 'redo', 'underline', 'undo',
                            ])
                            ->translateLabel()
                            ->columnSpanFull(),

                        Textarea::make('solution')
                            ->rows(5)
                            ->cols(20)
                            ->visible(auth()->user()->hasAnyRole(['Head', 'super admin', 'admin']))
                            ->translateLabel()
                            ->columnSpanFull(),
                    ])
                        ->columnSpan(1),

                    // Right Column (50%) - All fields in a single grid
                    Grid::make(2) // 2-column grid for all right-side fields
                    ->schema([
                        // Row 1
                        Select::make('system_id')
                            ->label(__('System'))
                            ->visible(auth()->user()->type == 2)
                            ->options(function () {
                                $user = auth()->user();
                                if ($user->type == 2 && $user->client) {
                                    $clientSystemIds = json_decode($user->client->system_id, true);
                                    if (empty($clientSystemIds) || !is_array($clientSystemIds)) {
                                        return [];
                                    }
                                    return array_filter(
                                        array_map(fn ($value) => __($value), self::$model::SYSTEM),
                                        fn ($key) => in_array($key, $clientSystemIds),
                                        ARRAY_FILTER_USE_KEY
                                    );
                                }
                                return collect(self::$model::SYSTEM)
                                    ->mapWithKeys(fn ($value, $key) => [$key => __($value)])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('service_id')
                            ->label('Service')
                            ->translateLabel()
                            ->options(collect(self::$model::SERVICES)
                                ->mapWithKeys(fn ($value, $key) => [$key => __($value)])
                                ->toArray())
                            ->formatStateUsing(fn ($record, $state) => __($state))
                            ->visible(auth()->user()->hasAnyRole(['Head', 'super admin', 'admin'])),

                        // Row 2
                        Select::make('client_id')
                            ->relationship('client', 'name')
                            ->disabled(fn ($get) => !empty($get('accepted_date')))
                            ->options(Client::with('user')
                                ->whereHas('user', fn ($query) => $query->active())
                                ->pluck('name', 'user_id'))
                            ->searchable()
                            ->required(auth()->user()->type == 1)
                            ->visible(auth()->user()->type == 1)
                            ->translateLabel(),

                        Select::make('assigned_to')
                            ->label('Assign To')
                            ->translateLabel()
                            ->options(function() {
                                $user = auth()->user();
                                $systemId = $user->hasAnyRole(['Head', 'super admin'])
                                    ? $user->admin->system_id
                                    : '';
                                return Admin::where('system_id', $systemId)
                                    ->whereHas('user', fn ($query) => $query
                                        ->where('status', 1)
                                        ->where('type', 1))
                                    ->pluck('name', 'user_id');
                            })
                            ->visible(auth()->user()->hasAnyRole(['Head', 'super admin'])),

                        // Row 3 (full width)
                        Textarea::make('recommendation')
                            ->translateLabel()
                            ->visibleOn('edit')
                            ->disabled(auth()->user()->hasRole('Client'))
                            ->columnSpanFull(),

                        // Row 4
                        DateTimePicker::make('accepted_date')
                            ->label('Accepted Date')
                            ->translateLabel()
                            ->native(false)
                            ->format('d-m-Y H:i')
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->visibleOn('edit')
                            ->visible(auth()->user()->hasAnyRole(['Head', 'super admin']))
                            ->disabled(fn ($get) => empty($get('accepted_date'))),

                        Toggle::make('isUrgent')
                            ->translateLabel(),
                    ])
                        ->columnSpan(1),
                ]),
            ]);
    }



    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {

        return $table
            ->query(function () {

                return static::getQueryBasedOnUserRole();
            })
            ->persistFiltersInSession()
            ->defaultPaginationPageOption(25)
            ->columns([
                    Tables\Columns\TextColumn::make('client.name')
                        ->label('Client')
                        ->description(fn (Ticket $record): ?string => __($record?->system_name) ?? null)
                        ->translateLabel()
                        ->extraAttributes(function (Ticket $ticket) {
                            if ($ticket->isUrgent && $ticket->assigned_to == auth()->id()) {
                                return [
                                    'class' => 'dark:bg-purple rounded-lg rounded bg-purple', // Specific color for both conditions
                                ];
                            }

                            // Check if the ticket is urgent
                            if ($ticket->isUrgent) {
                                return [
                                    'class' => 'dark:bg-danger rounded-lg rounded bg-danger', // Color for urgent tickets
                                ];
                            }

                            // Check if the ticket is assigned to the current user
                            if ($ticket->assigned_to == auth()->id()) {
                                return [
                                    'class' => 'dark:bg-success rounded-lg rounded bg-success', // Color for assigned tickets
                                ];
                            }

                            // Default case (no special conditions)
                            return [];
                        })
                        ->tooltip(fn (Ticket $record): string => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : ''),
                    Tables\Columns\TextColumn::make('accepted_date')
                        ->date('d/m/Y')
                        ->description(fn(Ticket $record): ?string => $record?->created_by == 1 ? __('TS') : __('Client'))
                        ->translateLabel()
                        ->toggleable(),
                            Tables\Columns\TextColumn::make('status')
                                ->badge()
                                ->toggleable()
                                ->translateLabel()
                                ->icon(function ($state) {
                                    return match ($state) {
                                        1 => 'heroicon-m-check-badge', // Resolved
                                        2 => 'heroicon-m-clock', // Pending
                                        3 => 'heroicon-m-arrow-path',    // In Progress
                                        4 => 'heroicon-m-currency-dollar',  // Paid
                                        default => 'secondary', // Default icon if status is not found
                                    };
                                })
                                ->color(function ($state) {
                                    return match ($state) {
                                        1 => 'success', // Resolved
                                        2 => 'warning', // Pending
                                        3 => 'info',    // In Progress
                                        4 => 'danger',  // Paid
                                        default => 'secondary', // Default color if status is not found
                                    };
                                })
                                ->formatStateUsing(function ($record,$state) {

                                    return [
                                        1 => auth()->user()->type == 1
                                            ? ($record->solved_by ? __('Resolved by') . ' ' . $record->admin->name : '')
                                            : __('Resolved'),
//                                    1=>__('Resolved by'),
                                        2 => __('Pending'),
                                        3 => __('In Progress'),
                                        4 => __('Paid'),
                                    ][$state] ?? 'Unknown';
                                }),
                             Tables\Columns\TextColumn::make('delivered_date')
                                ->date('d/m/Y')
                                ->translateLabel()
                                ->toggleable(),
                            Tables\Columns\TextColumn::make('description')
                                ->searchable()
                                ->html()
                                ->translateLabel()
                                ->sortable()
                                ->toggleable()
                                ->wrap(),
                    Tables\Columns\TextColumn::make('solution')
                        ->toggleable()
                        ->searchable()
                        ->translateLabel()
                        ->visible(auth()->user()->hasAnyRole(['Head', 'super admin', 'admin']))
                        ->wrap(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->date('d/m/Y')
                    ->toggleable()
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->formatStateUsing(function ($state) {
                        return $state ? __('Deleted') : '-'; // Display "Deleted" if soft deleted, otherwise show a dash
                    })
                    ->visible(auth()->user()->hasPermissionTo('delete_ticket'))
                    ->translateLabel()
                    ->label(__('Deleted'))



        ])
            ->filters([
                Tables\Filters\TrashedFilter::make('deleted_at'),

                Tables\Filters\SelectFilter::make('system_id')
                    ->label('System')
                    ->searchable()
                    ->options(self::$model::SYSTEM)
                    ->hidden(fn () => !auth()->user()->hasRole('super admin')),

                // Status filter (replaces SelectConstraint)
                Tables\Filters\SelectFilter::make('status')
                    ->options(self::$model::STATUS)
                    ->searchable()
                    ->multiple(), // Allows selecting multiple statuses

                // isUrgent filter (replaces BooleanConstraint)
                Tables\Filters\TernaryFilter::make('isUrgent')
                    ->label('Urgent Tickets')
                    ->trueLabel('Only Urgent')
                    ->falseLabel('Only Normal')
                    ->queries(
                        true: fn (Builder $query) => $query->where('isUrgent', true),
                        false: fn (Builder $query) => $query->where('isUrgent', false),
                        blank: fn (Builder $query) => $query,
                    ),

                // Client filter (replaces SelectConstraint)
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->searchable()
                    ->options(Client::all()->pluck('name', 'user_id'))
                    ->multiple(), // Allows selecting multiple clients

                // Assigned To filter (replaces SelectConstraint)
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->searchable()
                    ->options(function () {
                        $user = auth()->user();

                        if ($user->hasRole('super admin')) {
                            return Admin::active()->pluck('name', 'user_id');
                        }

                        if ($user->hasRole('Head')) {
                            return Admin::where('system_id', $user->admin->system_id)
                                ->active()
                                ->pluck('name', 'user_id');
                        }

                        return [];
                    })
                    ->hidden(fn () => !auth()->user()->hasAnyRole(['super admin', 'Head'])),
                Tables\Filters\TernaryFilter::make('all_tickets')
                    ->label('Show All Tickets')
                    ->trueLabel('All Tickets')
                    ->falseLabel('Current Year Only')
                    ->queries(
                        true: fn (Builder $query) => $query,
                        false: fn (Builder $query) => $query->whereYear('created_at', now()->year),
                        blank: fn (Builder $query) => $query->whereYear('created_at', now()->year),
                    )
                    ->default(false),
                // Date range filter (new addition)
                Filter::make('created_at')
                    ->form([
                        Grid::make(2) // This creates a 2-column grid
                        ->schema([
                            DatePicker::make('created_from')
                                ->label(__('From Date'))
                                ->columnSpan(1), // Each date picker takes full width of its column
                            DatePicker::make('created_until')
                                ->label(__('To Date'))
                                ->columnSpan(1),
                        ]),
                    ])->columnSpan(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('solved_by_me')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('solved_by', auth()->id()))
                    ->hidden(fn () => !auth()->user()->hasAnyRole(['admin', 'Head'])),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(''),

        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(auth()->user()->hasPermissionTo('delete_ticket')),
                ]),
            ]);
    }

    private static function getQueryBasedOnUserRole(): Builder
    {
        $user = auth()->user();
        // Start with base query
        $query = Ticket::query()
            ->withTrashed();

        // Apply role-specific conditions
        if ($user->hasRole('super admin')) {
            // No additional conditions for super admin
        } elseif ($user->hasRole('Head') || $user->hasRole('admin')) {
            $systemId = $user->admin->system_id;
            $query->where('system_id', $systemId);
            if ($user->hasRole('admin')) {
                $query->whereNotNull('service_id');
            }
        } else {
            $query->where('client_id', $user->id);
        }

        return $query
            ->orderByRaw('CASE WHEN status = 2 THEN 0 ELSE 1 END')
            ->orderBy('created_at', 'desc')
            ->orderBy('isUrgent', 'desc')
            ->orderBy('status', 'desc');
    }
    static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
