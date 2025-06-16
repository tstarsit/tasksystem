<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Ticket;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Get filters (if they exist)
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        // Parse dates if they exist and cast to date-only
        $startDate = $startDate ? Carbon::parse($startDate)->toDateString() : null;
        $endDate = $endDate ? Carbon::parse($endDate)->toDateString() : now()->toDateString();

        // Base query for tickets based on user role
        $baseQuery = $this->getBaseQuery($user);

        // Apply date filters if they exist
        if ($startDate) {
            $baseQuery->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        }
        // Calculate ticket counts with filters applied
        $ticketCounts = $this->getMonthlyCounts($baseQuery, $startDate, $endDate);

        // Get the count for the first and last month in the selected date range
        $firstMonthCountAll = $ticketCounts['all'][0] ?? 0;
        $lastMonthCountAll = $ticketCounts['all'][count($ticketCounts['all']) - 1] ?? 0;

        $firstMonthCountRequests = $ticketCounts['requests'][0] ?? 0;
        $lastMonthCountRequests = $ticketCounts['requests'][count($ticketCounts['requests']) - 1] ?? 0;

        $firstMonthCountMaintenance = $ticketCounts['maintenance'][0] ?? 0;
        $lastMonthCountMaintenance = $ticketCounts['maintenance'][count($ticketCounts['maintenance']) - 1] ?? 0;

        // Calculate percentage change for each category
        $percentageChangeAll = $this->calculatePercentageChange($lastMonthCountAll, $firstMonthCountAll);
        $percentageChangeRequests = $this->calculatePercentageChange($lastMonthCountRequests, $firstMonthCountRequests);
        $percentageChangeMaintenance = $this->calculatePercentageChange($lastMonthCountMaintenance, $firstMonthCountMaintenance);

        // Determine if the trend is increasing or decreasing for each category
        $isIncreasingAll = $lastMonthCountAll > $firstMonthCountAll;
        $isIncreasingRequests = $lastMonthCountRequests > $firstMonthCountRequests;
        $isIncreasingMaintenance = $lastMonthCountMaintenance > $firstMonthCountMaintenance;

        // Format the percentage change for each category
        $percentageChangeFormattedAll = number_format(abs($percentageChangeAll), 2) . '%';
        $percentageChangeFormattedRequests = number_format(abs($percentageChangeRequests), 2) . '%';
        $percentageChangeFormattedMaintenance = number_format(abs($percentageChangeMaintenance), 2) . '%';

        // Query for soft-deleted tickets in the selected date range
        $softDeletedTicketsCount = $this->getBaseQuery($user, true)
            ->when($startDate, fn (Builder $query) => $query->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate))
            ->count();

        // Query for soft-deleted tickets in the previous date range
        $previousStartDate = $startDate ? Carbon::parse($startDate)->subDays(Carbon::parse($startDate)->diffInDays($endDate))->toDateString() : null;
        $previousEndDate = $startDate ? Carbon::parse($startDate)->subDay()->toDateString() : null;

        $previousSoftDeletedTicketsCount = $this->getBaseQuery($user, true)
            ->when($previousStartDate, fn (Builder $query) => $query->whereDate('created_at', '>=', $previousStartDate)->whereDate('created_at', '<=', $previousEndDate))
            ->count();

        // Calculate percentage change for soft-deleted tickets
        $percentageChangeSoftDeleted = $this->calculatePercentageChange($softDeletedTicketsCount, $previousSoftDeletedTicketsCount);

        // Determine if the trend is increasing or decreasing for soft-deleted tickets
        $isIncreasingSoftDeleted = $softDeletedTicketsCount > $previousSoftDeletedTicketsCount;

        // Format the percentage change for soft-deleted tickets
        $percentageChangeFormattedSoftDeleted = number_format(abs($percentageChangeSoftDeleted), 2) . '%';

        // Get chart data for soft-deleted tickets
        $softDeletedTicketsChartData = $this->getSoftDeletedTicketsChartData($startDate, $endDate, $user);

        return [
            Stat::make(__('Total Tickets'), $baseQuery->count())
                ->color($isIncreasingAll ? 'success' : 'danger')
                ->chart($ticketCounts['all'])
                ->extraAttributes(['class' => 'shadow-2xl'])
                ->descriptionIcon($isIncreasingAll ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->description($isIncreasingAll ? __("Increasing by ") . $percentageChangeFormattedAll : __("Decreasing by ") . $percentageChangeFormattedAll),

            Stat::make(__('Total Requests'), $baseQuery->clone()->where('service_id', 2)->count())
                ->color($isIncreasingRequests ? 'success' : 'danger')
                ->chart($ticketCounts['requests'])
                ->extraAttributes(['class' => 'shadow-2xl'])
                ->descriptionIcon($isIncreasingRequests ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->description($isIncreasingRequests ? __("Increasing by ") . $percentageChangeFormattedRequests : __("Decreasing by ") . $percentageChangeFormattedRequests),

            Stat::make(__('Total Maintenance'), $baseQuery->clone()->where('service_id', 1)->count())
                ->color($isIncreasingMaintenance ? 'success' : 'danger')
                ->chart($ticketCounts['maintenance'])
                ->extraAttributes(['class' => 'shadow-2xl'])
                ->descriptionIcon($isIncreasingMaintenance ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->description($isIncreasingMaintenance ? __("Increasing by ") . $percentageChangeFormattedMaintenance : __("Decreasing by ") . $percentageChangeFormattedMaintenance),

            Stat::make(__('Soft Deleted Tickets'), $softDeletedTicketsCount)
                ->color($isIncreasingSoftDeleted ? 'warning' : 'gray')
                ->chart($softDeletedTicketsChartData)
                ->extraAttributes(['class' => 'shadow-2xl'])
                ->descriptionIcon($isIncreasingSoftDeleted ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->description($isIncreasingSoftDeleted ? __("Increasing by ") . $percentageChangeFormattedSoftDeleted : __("Decreasing by ") . $percentageChangeFormattedSoftDeleted),
        ];
    }

    /**
     * Get the base query for tickets based on the user's role.
     *
     * @param mixed $user
     * @param bool $onlyTrashed
     * @return Builder
     */
    private function getBaseQuery($user, bool $onlyTrashed = false): Builder
    {
        $query = $onlyTrashed ? Ticket::onlyTrashed() : Ticket::query();
        if ($user->hasRole('super admin')) {
            return $query;
        }


        return $user->type == 1
            ? $query->where('system_id', $user->admin->system_id)
            : $query->where('client_id', $user->id);
    }

    private function getMonthlyCounts($query, $startDate, $endDate): array
    {
        $counts = [
            'all' => [],
            'requests' => [],
            'maintenance' => [],
        ];

        // If no startDate is provided, default to the earliest ticket date
        if (!$startDate) {
            $startDate = Ticket::min('created_at');
            $startDate = $startDate ? Carbon::parse($startDate)->startOfMonth()->toDateString() : now()->startOfMonth()->toDateString();
        }

        // Ensure endDate is not in the future
        $endDate = min($endDate, now()->toDateString());

        // Loop through each month in the range
        $currentDate = Carbon::parse($startDate)->startOfMonth();
        $endDateCarbon = Carbon::parse($endDate)->endOfMonth();

        while ($currentDate <= $endDateCarbon) {
            $startOfMonth = $currentDate->copy()->startOfMonth()->toDateString();
            $endOfMonth = $currentDate->copy()->endOfMonth()->toDateString();

            // Calculate counts for the current month
            $counts['all'][] = $query->clone()->whereDate('created_at', '>=', $startOfMonth)->whereDate('created_at', '<=', $endOfMonth)->count();
            $counts['requests'][] = $query->clone()->where('service_id', 2)->whereDate('created_at', '>=', $startOfMonth)->whereDate('created_at', '<=', $endOfMonth)->count();
            $counts['maintenance'][] = $query->clone()->where('service_id', 1)->whereDate('created_at', '>=', $startOfMonth)->whereDate('created_at', '<=', $endOfMonth)->count();

            // Move to the next month
            $currentDate->addMonth();
        }

        return $counts;
    }

    private function getSoftDeletedTicketsChartData($startDate, $endDate, $user): array
    {
        $chartData = [];

        // If no startDate is provided, default to the earliest soft-deleted ticket date
        if (!$startDate) {
            $startDate = Ticket::onlyTrashed()->min('deleted_at');
            $startDate = $startDate ? Carbon::parse($startDate)->startOfMonth()->toDateString() : now()->startOfMonth()->toDateString();
        }

        // Ensure endDate is not in the future
        $endDate = min($endDate, now()->toDateString());

        // Loop through each month in the range
        $currentDate = Carbon::parse($startDate)->startOfMonth();
        $endDateCarbon = Carbon::parse($endDate)->endOfMonth();

        while ($currentDate <= $endDateCarbon) {
            $startOfMonth = $currentDate->copy()->startOfMonth()->toDateString();
            $endOfMonth = $currentDate->copy()->endOfMonth()->toDateString();

            // Count soft-deleted tickets for the current month
            $count = $this->getBaseQuery($user, true)
                ->whereDate('deleted_at', '>=', $startOfMonth)
                ->whereDate('deleted_at', '<=', $endOfMonth)
                ->count();

            // Add the count to the chart data
            $chartData[] = $count;

            // Move to the next month
            $currentDate->addMonth();
        }

        return $chartData;
    }

    private function calculatePercentageChange($newValue, $oldValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        if ($newValue == 0) {
            return $oldValue > 0 ? -100 : 0;
        }

        return (($newValue - $oldValue) / $oldValue) * 100;
    }
}
