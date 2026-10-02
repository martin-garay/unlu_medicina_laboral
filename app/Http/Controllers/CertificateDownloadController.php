<?php

namespace App\Http\Controllers;

use App\Domain\Auditoria\Services\AuditoriaAdministrativaService;
use App\Models\AnticipoCertificado;
use App\Models\AnticipoCertificadoArchivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CertificateDownloadController
{
    public function __invoke(Request $request, AnticipoCertificado $anticipo, AnticipoCertificadoArchivo $archivo)
    {
        abort_unless($request->user()?->can('backoffice.access') && $request->user()?->can('certificados.view')
            && $request->user()?->can('certificados.download'), 403);
        abort_unless($archivo->anticipo_certificado_id === $anticipo->id
            && $archivo->conversacion_id === $anticipo->conversacion_id
            && $archivo->estado_storage === 'confirmado', 404);
        $bytes = DB::table('anticipo_certificado_archivo_contenidos')->where('archivo_id', $archivo->id)->value('contenido');
        if (is_resource($bytes)) {
            $bytes = stream_get_contents($bytes);
        }
        abort_unless(is_string($bytes) && hash_equals($archivo->hash_archivo, hash('sha256', $bytes)), 404);
        app(AuditoriaAdministrativaService::class)->record('certificate.downloaded',
            AuditoriaAdministrativaService::ORIGIN_FILAMENT, $request->user(), $archivo);
        $name = 'certificado-'.$archivo->id.'.'.$archivo->extension;

        return response($bytes)->header('Content-Type', $archivo->mime_type)
            ->header('Content-Disposition', 'attachment; filename="'.$name.'"')
            ->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
    }
}
