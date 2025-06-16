<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Ticket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use function React\Promise\all;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationLabel(): string
    {
        return __('Users');
    }
    public static function getPluralLabel(): ?string
    {
        return  __('Users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-panels::pages/auth/register.form.name.label'))
                    ->required()
                    ->maxLength(255)
                    ->autofocus()
                    ->reactive()
                    ->debounce(1000)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $username = self::generateUsername($state); // Use self:: instead of $this->
                        $set('username', $username);
                    }),

                TextInput::make('username')
                    ->label(__('Username'))
                    ->required()
                    ->disabled()
                    ->dehydrated()
                    ->maxLength(255)
                    ->unique(User::class, 'username', ignoreRecord: true),

                Forms\Components\Select::make('roles') // Changed from 'role' to 'roles'
                ->relationship('roles', 'name')
                    ->multiple()
                    ->options(Role::all()->pluck('name', 'id')),

                Select::make('system_id')
                    ->label('System')
                    ->multiple()
                    ->options(Ticket::SYSTEM)
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules(['array']),
            ]);
    }
    protected static function transliterateArabicToEnglish(string $text): string
    {
        $arabic = [
            'ا', 'أ', 'إ', 'آ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ى', 'ة', 'ئ', 'ء', 'ؤ'
        ];

        $english = [
            'a', 'a', 'a', 'a', 'b', 't', 'th', 'j', 'h', 'kh', 'd', 'th', 'r', 'z', 's', 'sh', 's', 'd', 't', 'z', 'a', 'gh', 'f', 'q', 'k', 'l', 'm', 'n', 'h', 'w', 'y', 'a', 'a', 'e', 'a', 'o'
        ];

        return str_replace($arabic, $english, $text);
    }
    protected static function generateUsername(string $name): string
    {
        // Transliterate Arabic to English
        $transliteratedName = self::transliterateArabicToEnglish($name);

        // Split the name into parts
        $nameParts = explode(' ', trim($transliteratedName));

        // Get the first letter of the first name part
        $firstLetter = substr($nameParts[0], 0, 1);

        // Get the first letter of the second name part (if it exists)
        $secondLetter = isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '';

        // Generate the base username
        $username = strtolower($firstLetter . $secondLetter . 'h'); // Append 'h' as in the old logic

        // Ensure the username is unique
        $counter = 1;
        $originalUsername = $username;

        while (User::where('username', $username)->exists()) {
            // Append the next character from the first name part
            $username = strtolower($originalUsername . substr($nameParts[0], $counter, 1));
            $counter++;
        }

        return Str::upper($username); // Convert to uppercase as in the old logic
    }

    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('admin', function ($adminQuery) use ($search) {
                            $adminQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        })
                            ->orWhereHas('client', function ($clientQuery) use ($search) {
                                $clientQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('username', 'like', "%{$search}%");
                            });
                    })
                    ->formatStateUsing(fn ($record) => $record->type == 1 ? $record->admin?->name : $record->client?->name),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->formatStateUsing(function ($state){
                        return __($state);
                    })
                    ->translateLabel(),
                ToggleColumn::make('approved_at')
                    ->label('Status')
                    ->translateLabel()
                    ->updateStateUsing(function ($record, $state) {
                        $record->approved_at = $state ? Carbon::now() : null;
                        $record->status =  $record->status==1?0:1;
                        $record->save();
                    })

            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                              '1' => 'Admin', // Option for user_type = 1
                               '2' => 'Client', // Option for user_type = 2
                           ]),
                SelectFilter::make('roles')
                    ->relationship('roles', 'name') // Use the 'roles' relationship and filter by 'name'
                    ->options(Role::all('name', 'id')) // Fetch roles and map name => id
                    ->placeholder('All Roles'), // Placeholder text

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
