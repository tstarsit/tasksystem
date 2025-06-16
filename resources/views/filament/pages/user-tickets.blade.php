<x-filament::page>
    {{ $this->form }}

    @if ($this->selectedUser)
        <div class="mt-6">
            @php
                $stats = $this->getTicketStats();
            @endphp

            <div class="mt-6 grid grid-cols-1 mb-5 sm:grid-cols-3 gap-4">
                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{__('Total Tickets')}}</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $stats['total'] }}
                    </div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{__('Pending')}}</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $stats['pending'] }}
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{__('Maintenance')}}</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $stats['maintenance'] }}
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{__('Request')}}</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $stats['request'] }}
                    </div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{__('Deleted')}}</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $stats['deleted'] }}
                    </div>
                </x-filament::card>
            </div>


            <div class="mt-4">
                {{ $this->table }}
            </div>
        </div>
    @endif
</x-filament::page>
