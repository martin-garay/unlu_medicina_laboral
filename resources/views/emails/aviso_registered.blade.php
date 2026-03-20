Aviso registrado: {{ 'AV-' . $aviso->id }}

Nombre: {{ $aviso->nombre_completo ?? '-' }}
Legajo: {{ $aviso->legajo ?? '-' }}
Sede: {{ $aviso->sede ?? '-' }}
Jornada laboral: {{ $aviso->jornada_laboral ?? '-' }}
Tipo de ausentismo: {{ $aviso->tipo_ausentismo ?? '-' }}
Fecha desde: {{ optional($aviso->fecha_inicio)->format('d/m/Y') ?? '-' }}
Fecha hasta: {{ optional($aviso->fecha_fin)->format('d/m/Y') ?? '-' }}
Motivo: {{ $aviso->motivo ?? '-' }}
