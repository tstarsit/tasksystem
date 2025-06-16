@php
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;
@endphp

<x-filament-panels::page.simple>
        {{ FilamentView::renderHook(PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="register">
        <p class="text-center">
            {{ __('Your Account is still under approval. Admins will contact with you soon!') }}
        </p>
    </x-filament-panels::form>

    {{ FilamentView::renderHook(PanelsRenderHook::AUTH_REGISTER_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>
