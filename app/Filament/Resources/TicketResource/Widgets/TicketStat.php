<?php

namespace App\Filament\Resources\TicketResource\Widgets;

use App\Filament\Resources\TicketResource\Pages\ListTickets;
use App\Models\Ticket;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketStat extends StatsOverviewWidget
{
    use InteractsWithPageTable;
    protected function getStats(): array
    {
        // Get the base query with all filters applied
        $baseQuery = $this->getPageTableQuery();

        // Clone the base query and remove any 'status' conditions
        $queryWithoutStatus = $baseQuery->clone();


        // Get the count for each status
        $pendingCount = $queryWithoutStatus->clone()->where('status', 2)->count();
        $resolvedCount = $queryWithoutStatus->clone()->where('status', 1)->count();
        $inProgressCount = $queryWithoutStatus->clone()->where('status', 3)->count();
        $paidCount = $queryWithoutStatus->clone()->where('status', 4)->count();

        return [
            Stat::make(__('Pending Orders'), $pendingCount)
                ->color('primary')

                ->icon('heroicon-o-clock'),
            Stat::make(__('Resolved Orders'), $resolvedCount)
                ->icon('heroicon-o-check-circle'),
            Stat::make(__('In Progress Orders'), $inProgressCount)
            ->icon('heroicon-m-arrow-path'),
            Stat::make(__('Paid Orders'), $paidCount)
            ->icon('heroicon-o-currency-dollar'),
        ];
    }
    protected function getTablePage(): string
    {
        return ListTickets::class;
    }
}
