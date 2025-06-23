<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use App\Models\Admin;
use App\Models\Audit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }
    public static function getNavigationLabel(): string
    {
        return __('Audits');
    }

    /**
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('Audits');
    }
    public static function getPluralLabel(): ?string
    {
        return __('Audits');

    }

    // Optimize the base query with necessary relationships
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user.admin', // Eager load `admin` relationship for users with type 1
                'user.client', // Eager load `client` relationship for users with type other than 1
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Define your form schema here
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn () => null) // Disables row click navigation
            ->modifyQueryUsing(function ($query){
                return $query->orderBy('created_at','desc');
            })
            ->columns([
                TextColumn::make('user_id')
                    ->label('Name')
                    ->translateLabel()
                    ->formatStateUsing(function (Audit $audit) {
                        // Conditionally return the related name based on the user type
                        return $audit->user->type == 1
                            ? $audit->user->admin->name ?? 'N/A'
                            : $audit->user->client->name ?? 'N/A';
                    }),
                TextColumn::make('change_type')
                    ->formatStateUsing(function ($record, $state) {
                        return __(self::$model::Type[$state]) ?? 'Unknown';
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        1 => 'primary',    // Edit
                        2 => 'success',  // Assign
                        3 => 'danger',     // Delete
                        4 => 'info',   // Restore
                        default => 'gray', // Fallback for unknown values
                    })
                    ->icon(fn ($state) => match ($state) {
                        1 => 'heroicon-o-pencil',     // Edit (Pencil Icon)
                        2 => 'heroicon-o-user',   // Assign (User Plus Icon)
                        3 => 'heroicon-o-trash',      // Delete (Trash Icon)
                        4 => 'heroicon-o-arrow-path',    // Restore (Refresh Icon)
                        default => 'heroicon-o-question-mark-circle', // Unknown (Question Mark Icon)
                    })
                    ->label('Type')
                    ->translateLabel(),

                TextColumn::make('ticket_id')
                    ->translateLabel()
                    ->label('Ticket'),
                TextColumn::make('old_value')
                    ->wrap()
                    ->translateLabel()
                    ->label('Old Value'),
                TextColumn::make('new_value')
                    ->wrap()
                    ->translateLabel()
                    ->html(function ($record){
                        return $record->changed_column=='description';
                    })
                    ->formatStateUsing(function ($record, $state) {
                        return $record->changed_column == 'assigned_to'
                            ? Admin::find($state)?->name
                            : $state;
                    })
                    ->label('New Value'),

                TextColumn::make('changed_column')
                    ->translateLabel()
                    ->label('Changed Column'),
                TextColumn::make('created_at')
                    ->translateLabel()
                    ->label('Date'),
            ])
            ->filters([
                SelectFilter::make('user_type')
                    ->options([
                        '1' => 'Admin',
                        '2' => 'Client',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('user', function ($q) use ($data) {
                                $q->where('type', $data['value']);
                            });
                        }
                    })
                    ->translateLabel()
                    ->label('User Type'),

                // Filter by change type
                SelectFilter::make('change_type')
                    ->translateLabel()
                    ->options(
                        collect(Audit::Type)
                            ->mapWithKeys(fn ($value, $key) => [$key => __($value)])
                            ->toArray()
                    )
                    ->label('Action Type'),



                // Quick date filters
                TernaryFilter::make('created_at')
                    ->translateLabel()
                    ->label('Quick Date Filters')
                    ->placeholder(__('All time'))
                    ->trueLabel(__('Today'))
                    ->falseLabel(__('Yesterday'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereDate('created_at', today()),
                        false: fn (Builder $query) => $query->whereDate('created_at', today()->subDay()),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
//                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Define any relations here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
        ];
    }
}
