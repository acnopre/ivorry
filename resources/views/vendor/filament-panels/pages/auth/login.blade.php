<x-filament-panels::page.simple>
    <x-slot name="logo">
        <div style="display: flex; justify-content: center; width: 100%; margin-bottom: 2rem;">
            <img src="{{ asset('images/ivory-logo-login.svg') }}" alt="Logo" style="height: 100px;">
        </div>
    </x-slot>

    @if (filament()->hasRegistration())
    <x-slot name="subheading">
        {{ __('filament-panels::pages/auth/login.actions.register.before') }}
        {{ $this->registerAction }}
    </x-slot>
    @endif

    <div class="text-center mb-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">Sign in to your account to continue</p>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(
        \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
        scopes: $this->getRenderHookScopes()
    ) }}

    {{-- Filament Login Form --}}
    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>

    {{-- Divider --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">Or</span>
        </div>
    </div>

    {{-- Member Login Button --}}
    <div class="flex justify-center">
        <a href="{{ route('member.login') }}" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:hover:bg-primary-900/30 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 shadow-sm border border-primary-200 dark:border-primary-800">
            <x-heroicon-o-user-circle class="w-5 h-5" />
            Member Login
        </a>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(
        \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
        scopes: $this->getRenderHookScopes()
    ) }}
</x-filament-panels::page.simple>
