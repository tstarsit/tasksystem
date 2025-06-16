<?php

namespace App\Filament\Pages;

use App\Models\Ticket;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;
    protected ?string $maxContentWidth='full';

    public function filtersForm(Form $form): Form
    {
        // Fetch the oldest created_at date from the tickets table
        $oldestTicketDate = Ticket::min('created_at');

        // Provide a fallback date if no tickets exist
        $defaultStartDate = $oldestTicketDate
            ? Carbon::parse($oldestTicketDate)->format('Y-m-d')
            : now()->startOfYear()->format('Y-m-d');

        return $form
            ->schema([
                Section::make('Filters')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->native(false)
                            ->translateLabel()
                            ->displayFormat('Y-m-d')
                            ->default($defaultStartDate), // Set default to the oldest created_at date or fallback

                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->translateLabel()
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->default(now()->format('Y-m-d')), // Set default to the current date
                    ])
                    ->extraAttributes([
                        'class'=>'shadow-2xl'
                    ])
                    ->columns(2),
            ]);
    }




}
