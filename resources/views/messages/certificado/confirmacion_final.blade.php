Resumen para confirmar el anticipo de certificado médico

Nombre: {{ $nombre }}
Legajo: {{ $legajo }}
Número de aviso asociado: {{ $numero_aviso }}
Tipo de certificado: {{ $tipo_certificado }}
Cantidad de archivos: {{ $cantidad_archivos }}
@if(!empty($nombres_o_referencias_archivos))
Archivos: {{ is_array($nombres_o_referencias_archivos) ? implode(', ', $nombres_o_referencias_archivos) : $nombres_o_referencias_archivos }}
@endif

Si los datos son correctos, confirme el anticipo de certificado.
