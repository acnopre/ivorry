<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-md p-6 sm:p-8">
        <!-- Header -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-semibold">Set Your Password</h1>
            <p class="text-gray-500 mt-2 text-sm">Enter your new password below.</p>
        </div>


        <!-- Form -->
        <form wire:submit.prevent="save" class="space-y-5">
            {{ $this->form }}
            <br>
            <x-filament::button type="submit" class="w-full py-3 text-base font-medium">
                Save Password
            </x-filament::button>
        </form>
    </div>
</div>
