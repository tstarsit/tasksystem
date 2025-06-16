<?php

namespace App\Providers\Filament;

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


    protected function transliterateArabicToEnglish(string $text): string
    {
        $arabic = [
            'ا', 'أ', 'إ', 'آ', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي', 'ى', 'ة', 'ئ', 'ء', 'ؤ'
        ];

        $english = [
            'a', 'a', 'a', 'a', 'b', 't', 'th', 'j', 'h', 'kh', 'd', 'th', 'r', 'z', 's', 'sh', 's', 'd', 't', 'z', 'a', 'gh', 'f', 'q', 'k', 'l', 'm', 'n', 'h', 'w', 'y', 'a', 'a', 'e', 'a', 'o'
        ];

        return str_replace($arabic, $english, $text);
    }
    protected function generateUsername(?string $name): string
    {
        // Trim and check if the name is empty
        if (empty(trim($name))) {
            return '';
        }

        // Transliterate Arabic to English
        $transliteratedName = $this->transliterateArabicToEnglish($name);

        // Split the name into parts
        $nameParts = explode(' ', trim($transliteratedName));

        // Ensure there's at least one valid part
        if (empty($nameParts[0])) {
            return '';
        }

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

            // If we exceed the length of the first name part, break to avoid errors
            if ($counter >= strlen($nameParts[0])) {
                break;
            }
        }

        return Str::upper($username); // Convert to uppercase as in the old logic
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
                $username = $this->generateUsername($state);
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
