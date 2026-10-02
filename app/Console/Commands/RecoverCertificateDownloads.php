<?php

namespace App\Console\Commands;

use App\Jobs\DownloadCertificate;
use App\Models\AnticipoCertificadoArchivo;
use App\Services\Certificates\CertificateAttachmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class RecoverCertificateDownloads extends Command
{
    protected $signature = 'certificates:recover';

    protected $description = 'Recupera descargas pendientes sin perder identidad del adjunto';

    public function handle(): int
    {
        if (! app(CertificateAttachmentService::class)->enabled()) {
            return self::SUCCESS;
        }
        AnticipoCertificadoArchivo::where('estado_storage', 'pendiente')->where('updated_at', '<', now()->subMinutes((int) config('medicina_laboral.certificados.download.recovery_minutes')))
            ->orderBy('id')->limit((int) config('medicina_laboral.certificados.download.recovery_batch_size'))->get()->each(function ($file) {
                DB::transaction(function () use ($file) {
                    $f = AnticipoCertificadoArchivo::lockForUpdate()->find($file->id);
                    if ($f->estado_storage !== 'pendiente' || $f->updated_at->gt(now()->subMinutes((int) config('medicina_laboral.certificados.download.recovery_minutes')))) {
                        return;
                    }
                    Queue::connection('database')->push(new DownloadCertificate($f->id), '', 'certificates');
                    $f->touch();
                });
            });

        return self::SUCCESS;
    }
}
