<x-filament-panels::page>
    @php
        $record = $this->getRecord();
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Resumen de conversacion
            </x-slot>

            <dl class="grid gap-4 md:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">WhatsApp</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->wa_number ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">DNI</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->dni ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Canal</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->canal ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Flujo</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->tipo_flujo ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado actual</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->estado_actual ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Paso actual</dt>
                    <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->paso_actual ?: '-' }}</dd>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Hilo de mensajes
            </x-slot>

            <div class="space-y-4">
                @forelse ($this->mensajes() as $mensaje)
                    @php
                        $esEntrante = $mensaje->direccion === \App\Models\ConversacionMensaje::DIRECCION_ENTRANTE;
                    @endphp

                    <article @class([
                        'rounded-lg border p-4',
                        'border-primary-200 bg-primary-50 dark:border-primary-800 dark:bg-primary-950/30' => $esEntrante,
                        'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900' => ! $esEntrante,
                    ])>
                        <header class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span @class([
                                    'rounded-md px-2 py-1 text-xs font-medium',
                                    'bg-primary-600 text-white' => $esEntrante,
                                    'bg-gray-700 text-white dark:bg-gray-200 dark:text-gray-950' => ! $esEntrante,
                                ])>
                                    {{ $esEntrante ? 'Usuario' : 'Chatbot' }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $mensaje->direccion }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $mensaje->tipo_mensaje ?: '-' }}</span>
                            </div>
                            <time class="text-xs text-gray-500 dark:text-gray-400">
                                {{ optional($mensaje->created_at)->format('Y-m-d H:i:s') ?: '-' }}
                            </time>
                        </header>

                        <p class="mt-3 whitespace-pre-line text-sm text-gray-950 dark:text-white">
                            {{ $mensaje->contenido_texto ?: '-' }}
                        </p>

                        <dl class="mt-4 grid gap-3 text-xs md:grid-cols-4">
                            <div>
                                <dt class="font-medium text-gray-500 dark:text-gray-400">Step</dt>
                                <dd class="mt-1 text-gray-950 dark:text-white">{{ $mensaje->step_key ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500 dark:text-gray-400">Valido</dt>
                                <dd class="mt-1 text-gray-950 dark:text-white">{{ $mensaje->es_valido === null ? '-' : ($mensaje->es_valido ? 'si' : 'no') }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500 dark:text-gray-400">Motivo invalidez</dt>
                                <dd class="mt-1 text-gray-950 dark:text-white">{{ $mensaje->motivo_invalidez ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500 dark:text-gray-400">Message key</dt>
                                <dd class="mt-1 text-gray-950 dark:text-white">{{ $mensaje->message_key ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-500 dark:text-gray-400">Template</dt>
                                <dd class="mt-1 text-gray-950 dark:text-white">{{ $mensaje->template_name ?: '-' }}</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay mensajes registrados.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Eventos de trazabilidad
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="py-2 pr-4">Fecha</th>
                            <th class="py-2 pr-4">Evento</th>
                            <th class="py-2 pr-4">Step</th>
                            <th class="py-2 pr-4">Codigo</th>
                            <th class="py-2 pr-4">Descripcion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->eventos() as $evento)
                            <tr>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ optional($evento->created_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $evento->tipo_evento ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $evento->step_key ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $evento->codigo ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-950 dark:text-white">{{ $evento->descripcion ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-3 text-sm text-gray-500 dark:text-gray-400">No hay eventos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
