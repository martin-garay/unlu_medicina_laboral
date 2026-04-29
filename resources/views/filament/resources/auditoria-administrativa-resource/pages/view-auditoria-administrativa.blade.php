<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Resumen
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Actor</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->actor?->name ?? 'Sistema' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email actor</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->actor?->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Accion</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->action }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Origen</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->origin }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Auditable</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->auditable_type ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Auditable ID</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->auditable_id ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creado</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->created_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-3">
            <x-filament::section>
                <x-slot name="heading">
                    Before values
                </x-slot>

                <pre class="overflow-x-auto whitespace-pre-wrap break-words text-xs text-gray-950 dark:text-white">{{ json_encode($record->before_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    After values
                </x-slot>

                <pre class="overflow-x-auto whitespace-pre-wrap break-words text-xs text-gray-950 dark:text-white">{{ json_encode($record->after_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Metadata
                </x-slot>

                <pre class="overflow-x-auto whitespace-pre-wrap break-words text-xs text-gray-950 dark:text-white">{{ json_encode($record->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
