<?php

namespace App\Providers\Filament;

use App\Helpers\Helpers;
use App\Models\Client;
use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomRegister extends Register
{

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getUserFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }


    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('filament-panels::pages/auth/register.form.name.label'))
            ->required()
            ->placeholder(__('Enter hospital name'))
            ->maxLength(255)
            ->autofocus()
            ->reactive()
            ->hint(__('Please enter the hospital name')) // Add a warning hint
            ->hintIcon('heroicon-o-exclamation-triangle') // Add a warning icon
            ->hintColor('warning')
            ->debounce(1000)
            ->afterStateUpdated(function ($state, callable $set) {
                $username = Helpers::generateUsername($state);
                $set('username', $username);
            });
    }

    protected function handleRegistration(array $data): User
    {

        $user = User::create(
            ['username' => $data['username'],
                'password' => bcrypt($data['password']),
                'type' => 2,
            ]);
        Client::create(['name' => $data['name'], 'user_id' => $user->id,]);
        return $user;
    }

    protected function getUserFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('User Name'))
            ->required()
            ->disabled()
            ->dehydrated()
            ->maxLength(255)
            ->unique($this->getUserModel());

    }

//    protected function generateUsername($name)
//    {
//        $nameParts = explode(' ', trim($name));
//        $firstLetter = substr($nameParts[0], 0, 1);
//        $secondLetter = isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '';
//        $username = strtolower($firstLetter . $secondLetter . 'h');
//        $counter = 1;
//        while (User::where('username', $username)->exists()) {
//            $username = strtolower($firstLetter . $secondLetter . 'h' . substr($nameParts[0], $counter, 1));
//            $counter++;
//        }
//        return Str::upper($username);
//    }


}
