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
        $baseQuery = $this->getPageTableQuery();
        $queryWithoutStatus = $baseQuery->clone();
        $stats = [];


        // Apply date filtering (only if filters exist)
        $resolvedQuery = $queryWithoutStatus->clone()
            ->where('status', 1)
            ->where('solved_by', auth()->id());

        // Remove the duplicate `whereDate('delivered_date', today())`
        // Let the date range filter handle it
        $resolvedCount = $resolvedQuery->count();
        $pendingCount = $queryWithoutStatus->clone()->where('service_id', 0)->count();
        // Rest of your stats...
        $assignedCount = $queryWithoutStatus->clone()->where('status', 3)->where('assigned_to', auth()->id())->count();
        $inProgressCount = $queryWithoutStatus->clone()
            ->where('status', 3)
            ->where('service_id', 1)
            ->count();
        $requestCount = $queryWithoutStatus
            ->clone()
            ->where('service_id', 2)
            ->where('status',3)
            ->whereNull('solution')
            ->count();


        $stats[] = Stat::make(__('Pending Tasks'), $pendingCount)
            ->color('primary')
            ->icon('heroicon-o-clock');

        $stats[] = Stat::make(__('In Progress Tasks'), $inProgressCount)
            ->color('primary')
            ->icon('heroicon-o-clock');

        $stats[] = Stat::make(__('Assigned Tasks'), $assignedCount)
            ->color('primary')
            ->icon('heroicon-o-clock');

        $stats[] = Stat::make(__('Total Requests'), $requestCount)
            ->color('primary')
            ->icon('heroicon-o-clock');

        $stats[] = Stat::make(__('Resolved Tasks'), $resolvedCount)
            ->icon('heroicon-o-check-circle');

        return $stats;
    }
    protected function getTablePage(): string
    {
        return ListTickets::class;
    }
}
