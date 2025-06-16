<?php

namespace App\Providers\Filament;
use App\Filament\Pages\VerificationPending;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Str;

class CustomLogin extends BaseLogin implements LoginResponseContract
{
    protected static string $view = 'filament.auth.login';

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getUserNameFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    protected function getUserNameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('User Name'))
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => Str::lower($data['username']), // Convert input to lowercase
            'password' => $data['password'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        // Convert username to lowercase for comparison
        $username = Str::lower($data['username']);
        $password = $data['password'];

        // Find user with case-insensitive username
        $user = Filament::auth()
            ->getProvider()
            ->getModel()
            ::whereRaw('LOWER(username) = ?', [$username])
            ->first();

        if (!$user || !Filament::auth()->getProvider()->validateCredentials($user, ['password' => $password])) {
            $this->throwFailureValidationException();
        }

        // Manually log in the user
        Filament::auth()->login($user, $data['remember'] ?? false);

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function getAuthenticatedResponse(): LoginResponseContract
    {
        return new class implements LoginResponseContract {
            public function toResponse($request): \Illuminate\Http\RedirectResponse
            {
                $user = $request->user();

                if ($user->approved_at) {
                    // User is verified - redirect to admin dashboard
                    return redirect()->route('filament.resources.tickets.index');
                }

                // User not verified - redirect to verification page
                return redirect()->to(VerificationPending::getUrl());
            }
        };
    }

    public function toResponse($request)
    {
        return redirect()->route('filament.resources.tickets.index');
    }

    protected function getViewData(): array
    {
        return parent::getViewData();
    }
}
