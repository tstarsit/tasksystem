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

        // Initialize stats array
        $stats = [];

        // Get the count for each status
        if (!auth()->user()->hasRole('Head')) {
            $pendingCountSystem = $queryWithoutStatus->clone()->where('status', 2)->count();
            $stats[] = Stat::make(
                __('Pending Tasks for ') . (auth()->user()->type == 1 ? __(Ticket::SYSTEM[auth()->user()->admin->system_id]) : __('all systems')),
                $pendingCountSystem
            )->color('primary');
        }

        $pendingCountUser = $queryWithoutStatus->clone()->where('status', 3)->where('assigned_to', auth()->id())->count();
        $resolvedCount = $queryWithoutStatus->clone()->where('status', 1)->where('solved_by', auth()->id())->count();
        $inProgressCount = $queryWithoutStatus->clone()->where('status', 3)->count();

        // Add remaining stats
        $stats[] = Stat::make(__('In Progress Tasks'), $inProgressCount)
            ->color('primary')
            ->icon('heroicon-o-clock');

        $stats[] = Stat::make(__('Assigned Tasks'), $pendingCountUser)
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
