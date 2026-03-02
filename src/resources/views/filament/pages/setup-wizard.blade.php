<x-filament-panels::page>
    <div class="mb-6 text-center">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Complete the steps below to finish setting up your store portal.
            You can update these details anytime from <strong>Settings &rarr; Store Profile</strong>.
        </p>
    </div>

    <form wire:submit="completeSetup">
        {{ $this->form }}
    </form>
</x-filament-panels::page>
