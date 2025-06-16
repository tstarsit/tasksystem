<?php
namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Ticket;
use Filament\Forms\Components\DateTimePicker;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Support\Htmlable;
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
        return MaxWidth::FitContent;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('page_UserTickets');
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
                        ->columnSpan(1), // Adjust column span as needed

                    Select::make('selectedUser')
                        ->label('Select User')
                        ->options(fn () => $this->getUserOptions())
                        ->searchable()
                        ->reactive()
                        ->translateLabel()
                        ->afterStateUpdated(fn () => $this->resetTable())
                        ->columnSpan(3), // Adjust column span as needed
                ])
                ->columns(4) // Total columns in the grid
                ->extraAttributes(['class' => 'gap-4']), // Optional: add gap between fields
        ];
    }
    public function getTicketStats(): array
    {
        if (! $this->selectedUser) {
            return [
                'total' => 0,
                'pending' => 0,
                'maintenance' => 0,
                'request' => 0,
                'deleted' => 0,
            ];
        }

        // Start with base query and include trashed upfront
        $query = Ticket::withTrashed();


        // Apply the filter based on role
        if ($this->role === 'client') {
            $query->where('client_id', $this->selectedUser);
        } elseif ($this->role === 'admin') {
            $query->where('solved_by', $this->selectedUser);
        }

        // Clone AFTER setting withTrashed + filters
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

            'admin' => User::role(['admin', 'Head']) // Accept both roles
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

            Tables\Filters\Filter::make('created_at')
                ->form([
                    DateTimePicker::make('created_from')
                        ->label('From Date'),
                    DateTimePicker::make('created_until')
                        ->label('To Date'),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['created_from'],
                            fn ($query) => $query->where('created_at', '>=', $data['created_from'])
                        )
                        ->when($data['created_until'],
                            fn ($query) => $query->where('created_at', '<=', $data['created_until'])
                        );
                })
                ->indicateUsing(function (array $data): ?string {
                    if (!$data['created_from'] && !$data['created_until']) {
                        return null;
                    }

                    return 'Date range: ' .
                        ($data['created_from'] ? $data['created_from']->format('M j, Y') : '∞') .
                        ' - ' .
                        ($data['created_until'] ? $data['created_until']->format('M j, Y') : '∞');
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
            Tables\Columns\TextColumn::make('delivered_date')
                ->date('d/m/Y')
                ->translateLabel()
                ->toggleable(),
            TextColumn::make('description')
                ->wrap()
                ->label('Description'),
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
                        2 => __('Pending'),
                        3 => __('In Progress'),
                        4 => __('Paid'),
                    ][$state] ?? 'Unknown';
                }),
            TextColumn::make('solution')
            ->wrap(),
        ];
    }



}
