Resumen para confirmar el aviso de ausencia

Nombre: {{ $nombre }}
Legajo: {{ $legajo }}
Sede: {{ $sede }}
Jornada laboral: {{ $jornada }}
Fecha desde: {{ $fecha_desde }}
Fecha hasta: {{ $fecha_hasta }}
Días informados: {{ $dias }}
Tipo de ausentismo: {{ $tipo_ausentismo }}
@if(!empty($nombre_familiar))
Familiar: {{ $nombre_familiar }}
@endif
@if(!empty($parentesco))
Parentesco: {{ $parentesco }}
@endif
Motivo: {{ $motivo }}
@if(!empty($domicilio_circunstancial))
Domicilio circunstancial: {{ $domicilio_circunstancial }}
@endif
@if(!empty($observaciones))
Observaciones: {{ $observaciones }}
@endif

1. Confirmar aviso
2. Cancelar y volver al menú principal
