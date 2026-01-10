<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
    <x-slot name="subheading">
        {{ __('filament-panels::pages/auth/login.actions.register.before') }}
        {{ $this->registerAction }}
    </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(
        \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
        scopes: $this->getRenderHookScopes()
    ) }}

    {{-- Filament Login Form --}}
    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>

    {{-- Member Login Button --}}
    <div class="mt-4 flex justify-center">
        <a href="{{ route('member.login') }}" class="inline-flex items-center justify-center px-6 py-2 font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 hover:text-primary-700 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2">
            Member Login
        </a>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(
        \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
        scopes: $this->getRenderHookScopes()
    ) }}
</x-filament-panels::page.simple>
