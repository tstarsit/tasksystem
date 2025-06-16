<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Ticket;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class TicketsOverviewChart extends ChartWidget
{
    protected static ?string $heading = 'Tickets';
    protected static ?int $sort = 2;
    public ?string $filter = 'week';


//public static function canView(): bool
//{
////        dd(auth()->user()->hasPermissionTo('widget_TicketsOverviewChart'));
//
//    return  auth()->user()->hasPermissionTo('widget_TicketsOverviewChart');
//}


    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last week',
            'month' => 'Last month',
            'year' => 'This year',
        ];
    }

    protected function getData(): array
    {
        $start = null;
        $end = null;
        $perData = null;
        switch ($this->filter) {
            case 'week':
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
                $perData = 'perDay';
                break;
            case 'month':
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                $perData = 'perDay';
                break;
            case 'year':
                $start = now()->startOfYear();
                $end = now()->endOfYear();
                $perData = 'perMonth';
                break;
        }

        $data = Trend::model(Ticket::class)
            ->between(
                start: $start,
                end: $end,
            )
            ->$perData()
            ->count();

        // Format the labels as month names
        $labels = $data->map(function (TrendValue $value) {
            return \Carbon\Carbon::parse($value->date)->format('M'); // 'M' gives the short month name (e.g., "Jan", "Feb")
        });

        return [
            'datasets' => [
                [
                    'label' => 'Tickets data',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
