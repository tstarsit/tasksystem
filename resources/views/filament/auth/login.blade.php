
<x-filament-panels::page.simple>
    <style>
        .fi-simple-layout {
            position: relative;
        }
        .star {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255, 184, 34, 0.8); /* Yellow color for stars */
            transform: rotate(45deg);
            box-shadow: 0 0 5px rgba(255, 184, 34, 0.8), 0 0 10px rgba(255, 184, 34, 0.8);
            animation: twinkle 2s infinite ease-in-out;
        }
        .star1 {
            top: 10%;
            left: 50%;
        }

        .star2 {
            top: 65%;
            left: 30%;
        }

        .star3 {
            top: 40%;
            left: 70%;
        }

        .star4 {
            top: 90%;
            left: 10%;
        }

        .star5 {
            top: 60%;
            left: 90%;
        }

        .star6 {
            top: 60%;
            left: 3%;
        }

        .star7 {
            top: 20%;
            left: 90%;
        }

        @keyframes twinkle {
            0%, 100% {
                transform: scale(1) rotate(45deg);
                opacity: 1;
            }
            50% {
                transform: scale(1.5) rotate(45deg);
                opacity: 0.5;
            }
        }
    </style>
    <div class="star star1"></div>
    <div class="star star2"></div>
    <div class="star star3"></div>
    <div class="star star4"></div>
    <div class="star star5"></div>
    <div class="star star6"></div>
    <div class="star star7"></div>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate" class="fi-simple-layout star-2 star-3">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>
