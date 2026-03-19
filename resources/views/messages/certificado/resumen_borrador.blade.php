Resumen actual del anticipo de certificado médico

Nombre: {{ $nombre }}
Legajo: {{ $legajo }}
Sede: {{ $sede }}
Jornada laboral: {{ $jornada }}
Número de aviso asociado: {{ $numero_aviso }}
Tipo de certificado: {{ $tipo_certificado }}
Cantidad de archivos: {{ $cantidad_archivos }}
@if(!empty($nombres_o_referencias_archivos))
Archivos: {{ is_array($nombres_o_referencias_archivos) ? implode(', ', $nombres_o_referencias_archivos) : $nombres_o_referencias_archivos }}
@endif

@if(!empty($mensaje_estado))
{{ $mensaje_estado }}
@endif
