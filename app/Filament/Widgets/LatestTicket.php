<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTicket extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $query = $user->hasRole('super admin')
            ? Ticket::query()->orderBy('created_at', 'desc')->orderBy('isUrgent', 'desc')
            : ($user->type == 1
                ? Ticket::where('system_id', $user->admin->system_id)->orderBy('created_at', 'desc')->orderBy('isUrgent', 'desc')
                : Ticket::where('client_id', $user->id)->orderBy('created_at', 'desc')->orderBy('isUrgent', 'desc'));
        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->translateLabel()
                    ->description(fn(Ticket $record): ?string => $record?->system_name ?? null)
                    ->tooltip(fn(Ticket $record): string => $record?->created_at ?? null),
                TextColumn::make('status')
                    ->translateLabel()
                    ->color(function ($state) {
                        return match ($state) {
                            1 => 'success', // Resolved
                            2 => 'warning', // Pending
                            3 => 'info',    // In Progress
                            4 => 'primary', // Paid
                            default => 'secondary', // Default color if status is not found
                        };
                    })
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return [
                            1 => __('Resolved'),
                            2 => __('Pending'),
                            3 => __('In Progress'),
                            4 => __('Paid'),
                        ][$state] ?? 'Unknown'; // Default label if status is not found
                    }),
                Tables\Columns\TextColumn::make('delivered_date')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('description')
                    ->translateLabel()
                    ->html()
                    ->wrap(),
            ])
            ->recordClasses(function (Ticket $record) {
                return [
                    'bg-danger-100 dark:bg-danger-400' => $record->isUrgent,
                    'bg-success-100 dark:bg-success-400' => $record->assigned_to == auth()->id(),
                ];
            })
            ->actions([
                Tables\Actions\Action::make(__('open'))
                    ->url(fn(Ticket $record): string => TicketResource::getUrl('edit', ['record' => $record])),
            ])
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'lg' => 3,
            ]);
    }
}
