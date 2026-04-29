<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Datos del agente
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->nombre_completo ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Legajo</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->legajo ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sede</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->sede ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jornada</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->jornada_laboral ?? '-' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Datos del certificado
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Numero</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->numero_anticipo ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->tipo_certificado ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->estado ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">WhatsApp</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->wa_number ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Registrado</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->registrado_en?->format('Y-m-d H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Creado</dt>
                    <dd class="text-sm text-gray-950 dark:text-white">{{ $record->created_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Observaciones
            </x-slot>

            <p class="whitespace-pre-line text-sm text-gray-950 dark:text-white">{{ $record->observaciones ?: '-' }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Relaciones
            </x-slot>

            <div class="grid gap-6 xl:grid-cols-2">
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Conversacion asociada</h3>
                    @if ($record->conversacion)
                        <dl class="grid gap-3 md:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                                <dd class="text-sm text-gray-950 dark:text-white">{{ $record->conversacion->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">WhatsApp</dt>
                                <dd class="text-sm text-gray-950 dark:text-white">{{ $record->conversacion->wa_number ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                                <dd class="text-sm text-gray-950 dark:text-white">{{ $record->conversacion->estado_actual ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Paso</dt>
                                <dd class="text-sm text-gray-950 dark:text-white">{{ $record->conversacion->paso_actual ?? '-' }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin conversacion asociada.</p>
                    @endif
                </div>

                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Aviso legacy asociado</h3>
                    @if ($record->aviso)
                        <dl class="grid gap-3 md:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                                <dd class="text-sm text-gray-950 dark:text-white">{{ $record->aviso->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">DNI</dt>
                                <dd class="text-sm text-gray-950 dark:text-white">{{ $record->aviso->dni ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                                <dd class="text-sm text-gray-950 dark:text-white">{{ $record->aviso->estado ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo ausentismo</dt>
                                <dd class="text-sm text-gray-950 dark:text-white">{{ $record->aviso->tipo_ausentismo ?? '-' }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin aviso legacy asociado.</p>
                    @endif
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Avisos asociados
            </x-slot>

            @php($avisos = $this->avisos())

            @if ($avisos->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Sin avisos asociados por relacion N a N.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-2">ID</th>
                                <th class="px-3 py-2">DNI</th>
                                <th class="px-3 py-2">Nombre</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2">Origen</th>
                                <th class="px-3 py-2">Vinculo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($avisos as $aviso)
                                <tr>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $aviso->id }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $aviso->dni ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $aviso->nombre_completo ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $aviso->estado ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $aviso->pivot->origen ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $aviso->pivot->estado_vinculo ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Archivos asociados
            </x-slot>

            @php($archivos = $this->archivos())

            @if ($archivos->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Sin archivos asociados.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-2">Nombre</th>
                                <th class="px-3 py-2">MIME</th>
                                <th class="px-3 py-2">Ext.</th>
                                <th class="px-3 py-2">Bytes</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2">Motivo rechazo</th>
                                <th class="px-3 py-2">Hash</th>
                                <th class="px-3 py-2">Disk</th>
                                <th class="px-3 py-2">Creado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($archivos as $archivo)
                                <tr>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->nombre_original ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->mime_type ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->extension ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->size_bytes ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->estado_validacion ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->motivo_rechazo ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->hash_archivo ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->storage_disk ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-950 dark:text-white">{{ $archivo->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
