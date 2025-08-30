<?php
namespace App\Filament\Pages;

use App\Exports\UserTicketExport;
use App\Models\User;
use App\Models\Ticket;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Event\Telemetry\System;

class UserTickets extends Page implements Forms\Contracts\HasForms, HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use InteractsWithTable;

    public ?string $selectedUser = null;
    public string $role = 'client';

    protected static string $view = 'filament.pages.user-tickets';
    protected static ?string $navigationIcon = 'heroicon-s-document';

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public function getTitle(): string|Htmlable
    {
        return __('User Tickets');
    }

    public static function getNavigationLabel(): string
    {
        return __('User Tickets');
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full; // Changed from MaxContent to Full
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('page_UserTickets');
    }

    public function getFilteredTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->getTableQuery()->withTrashed();

        // Apply the role-based filter
        if ($this->role === 'client') {
            $query->where('client_id', $this->selectedUser);
        } elseif ($this->role === 'admin') {
            $query->where('solved_by', $this->selectedUser);
        }

        // Apply all active table filters
        foreach ($this->getTableFilters() as $filter) {
            $filter->apply(
                $query,
                $this->tableFilters[$filter->getName()] ?? []
            );
        }

        return $query;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
                    Select::make('role')
                        ->label('Select Role')
                        ->options([
                            'client' => 'Client',
                            'admin' => 'Admin',
                        ])
                        ->translateLabel()
                        ->default('client')
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->role = $state)
                        ->columnSpan(1),

                    Select::make('selectedUser')
                        ->label('Select User')
                        ->options(fn () => $this->getUserOptions())
                        ->searchable()
                        ->reactive()
                        ->translateLabel()
                        ->afterStateUpdated(fn () => $this->resetTable())
                        ->columnSpan(3),
                ])
                ->columns(4)
                ->extraAttributes(['class' => 'gap-4']),
        ];
    }

    public function getTicketStats(): array
    {
        if (!$this->selectedUser) {
            return [
                'total' => 0,
                'pending' => 0,
                'maintenance' => 0,
                'request' => 0,
                'deleted' => 0,
            ];
        }

        $query = $this->getFilteredTableQuery();
        $baseQuery = clone $query;

        return [
            'total' => $baseQuery->count(),
            'pending' => (clone $baseQuery)->where('service_id', 0)->count(),
            'maintenance' => (clone $baseQuery)->where('service_id', 1)->count(),
            'request' => (clone $baseQuery)->where('service_id', 2)->count(),
            'deleted' => (clone $baseQuery)->whereNotNull('deleted_at')->count(),
        ];
    }

    protected function getUserOptions()
    {
        return match ($this->role) {
            'client' => User::role('Client')
                ->whereHas('client')
                ->with('client')
                ->get()
                ->mapWithKeys(fn ($user) => [
                    $user->id => ($user->client->name ?? 'Unnamed') . ' (' . $user->username . ')',
                ]),

            'admin' => User::role(['admin', 'Head'])
                ->where('type', 1)
                ->active()
                ->whereHas('admin')
                ->with('admin')
                ->get()
                ->mapWithKeys(fn ($user) => [
                    $user->id => ($user->admin->name ?? 'Unnamed') . ' (' . $user->username . ')',
                ]),

            default => [],
        };
    }

    protected function getTableQuery()
    {
        return Ticket::query()
            ->when($this->selectedUser, function ($query) {
                if ($this->role === 'client') {
                    $query->where('client_id', $this->selectedUser);
                } elseif ($this->role === 'admin') {
                    $query->where('solved_by', $this->selectedUser);
                }
            })->orderBy('created_at','desc');
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('system_id')
                ->options(Ticket::SYSTEM)
                ->label('System Type')
                ->placeholder('All Systems'),

            Tables\Filters\SelectFilter::make('status')
                ->options(Ticket::STATUS)
                ->label('Ticket Status')
                ->placeholder('All Statuses'),

            Tables\Filters\SelectFilter::make('service_id')
                ->options(Ticket::SERVICES)
                ->label('Service Type')
                ->placeholder('All Services'),

            Tables\Filters\Filter::make('delivered_date')
                ->form([
                    DatePicker::make('created_from')
                        ->label(__('From Date'))
                        ->columnSpan(1),
                    DatePicker::make('created_until')
                        ->label(__('To Date'))
                        ->columnSpan(1),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('delivered_date', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('delivered_date', '<=', $date),
                        );
                }),

            Tables\Filters\Filter::make('is_urgent')
                ->label('Urgent Tickets Only')
                ->query(fn ($query) => $query->where('is_urgent', true)),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('client.name')
                ->label('Client')
                ->description(fn (Ticket $record): ?string => __($record?->system_name) ?? null)
                ->translateLabel()
                ->toggleable()
                ->extraAttributes(function (Ticket $ticket) {
                    if ($ticket->isUrgent && $ticket->assigned_to == auth()->id()) {
                        return ['class' => 'dark:bg-purple rounded-lg rounded bg-purple'];
                    }
                    if ($ticket->isUrgent) {
                        return ['class' => 'dark:bg-danger rounded-lg rounded bg-danger'];
                    }
                    if ($ticket->assigned_to == auth()->id()) {
                        return ['class' => 'dark:bg-success rounded-lg rounded bg-success'];
                    }
                    return [];
                })
                ->tooltip(fn (Ticket $record): string => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : ''),

            Tables\Columns\TextColumn::make('delivered_date')
                ->date('d/m/Y')
                ->translateLabel()
                ->toggleable(),

            TextColumn::make('description')
                ->wrap()
                ->html()
                ->translateLabel()
                ->label('Description'),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->toggleable()
                ->translateLabel()
                ->icon(function ($state) {
                    return match ($state) {
                        1 => 'heroicon-m-check-badge',
                        2 => 'heroicon-m-clock',
                        3 => 'heroicon-m-arrow-path',
                        4 => 'heroicon-m-currency-dollar',
                        default => 'secondary',
                    };
                })
                ->color(function ($state) {
                    return match ($state) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'info',
                        4 => 'danger',
                        default => 'secondary',
                    };
                })
                ->formatStateUsing(function ($record,$state) {
                    return [
                        1 => auth()->user()->type == 1
                            ? ($record->solved_by ? __('Resolved by') . ' ' . $record->admin->name : '')
                            : __('Resolved'),
                        2 => __('Pending'),
                        3 => __('In Progress'),
                        4 => __('Paid'),
                    ][$state] ?? 'Unknown';
                }),

            TextColumn::make('solution')
                ->wrap()
                ->translateLabel(),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export')
                ->translateLabel()
                ->icon('icon-excel')
                ->action(function () {
                    return Excel::download(
                        new UserTicketExport($this->filterTableQuery($this->getTableQuery())->get()),
                        'user-tickets-'.now()->format('Y-m-d').'.xlsx'
                    );
                })
                ->color('success')
                ->hidden(!$this->selectedUser),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            ExportBulkAction::make()
                ->label(__('Export Selected'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => (bool) $this->selectedUser)
        ];
    }

    // Add this method to configure the table for full width
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->filters($this->getTableFilters())
            ->headerActions($this->getTableHeaderActions())
            ->bulkActions($this->getTableBulkActions())
            ->contentGrid(['md' => 2, 'xl' => 3]) // Optional: adjust grid layout
            ->paginated([10, 25, 50, 100, 'all']) // Optional: customize pagination
            ->deferLoading() // Optional: improve performance
            ->striped();
    }
}
