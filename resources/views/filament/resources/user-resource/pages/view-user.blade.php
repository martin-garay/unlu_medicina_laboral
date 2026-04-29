<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Datos del usuario
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Admin local</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->is_admin ? 'Si' : 'No' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creado</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->created_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Roles
            </x-slot>

            @php($roles = $record->roles()->orderBy('name')->get())

            @if ($roles->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Sin roles asignados.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($roles as $role)
                        <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                            {{ $role->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
