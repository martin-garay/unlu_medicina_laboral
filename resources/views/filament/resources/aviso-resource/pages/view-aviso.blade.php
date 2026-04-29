<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $conversacion = $record->conversacion;
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Datos del agente
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">DNI</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->dni ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->nombre_completo ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Legajo</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->legajo ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sede</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->sede ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jornada</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->jornada_laboral ?: '-' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Datos del aviso
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->tipo ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->estado ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo ausentismo</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->tipo_ausentismo ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha inicio</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ optional($record->fecha_inicio)->format('Y-m-d') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha fin</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ optional($record->fecha_fin)->format('Y-m-d') ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dias</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->cantidad_dias ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">WhatsApp</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->wa_number ?: '-' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Observaciones
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Motivo</dt>
                    <dd class="mt-1 whitespace-pre-line text-sm text-gray-950 dark:text-white">{{ $record->motivo ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Domicilio circunstancial</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->domicilio_circunstancial ?: '-' }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Observaciones</dt>
                    <dd class="mt-1 whitespace-pre-line text-sm text-gray-950 dark:text-white">{{ $record->observaciones ?: '-' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Conversacion asociada
            </x-slot>

            @if ($conversacion)
                <dl class="grid gap-4 md:grid-cols-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                        <dd class="mt-1 text-sm text-gray-950 dark:text-white">#{{ $conversacion->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">WhatsApp</dt>
                        <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $conversacion->wa_number ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                        <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $conversacion->estado_actual ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Paso</dt>
                        <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $conversacion->paso_actual ?: '-' }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay conversacion asociada.</p>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Anticipos asociados
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="py-2 pr-4">Numero</th>
                            <th class="py-2 pr-4">Estado</th>
                            <th class="py-2 pr-4">Tipo</th>
                            <th class="py-2 pr-4">Origen</th>
                            <th class="py-2 pr-4">Registrado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->anticiposCertificado() as $anticipo)
                            <tr>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $anticipo->numero_anticipo ?: '#' . $anticipo->id }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $anticipo->estado ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $anticipo->tipo_certificado ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $anticipo->pivot?->origen ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ optional($anticipo->registrado_en)->format('Y-m-d H:i:s') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-3 text-sm text-gray-500 dark:text-gray-400">No hay anticipos asociados por pivot.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Anticipos legacy
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="py-2 pr-4">Numero</th>
                            <th class="py-2 pr-4">Estado</th>
                            <th class="py-2 pr-4">Tipo</th>
                            <th class="py-2 pr-4">Registrado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->anticiposCertificadoLegacy() as $anticipo)
                            <tr>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $anticipo->numero_anticipo ?: '#' . $anticipo->id }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $anticipo->estado ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $anticipo->tipo_certificado ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ optional($anticipo->registrado_en)->format('Y-m-d H:i:s') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-3 text-sm text-gray-500 dark:text-gray-400">No hay anticipos legacy asociados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
