<?php

namespace App\Providers\Filament;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\EditProfile;

class CustomProfile extends EditProfile
{
    public static function isSimple(): bool
    {
        return false;
    }

    protected ?string $maxContentWidth = 'full';

    protected function getUserFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('Username'))
            ->maxLength(255)
            ->unique(ignoreRecord: true);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getUserFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(!static::isSimple()),
            ),
        ];
    }


}
