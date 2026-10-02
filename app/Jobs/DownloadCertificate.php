<?php

namespace App\Jobs;

use App\Services\Certificates\CertificateAttachmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DownloadCertificate implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(public readonly int $attachmentId)
    {
        $this->tries = (int) config('medicina_laboral.certificados.download.tries');
        $this->timeout = (int) config('medicina_laboral.certificados.download.job_timeout_seconds');
        $this->onConnection('database');
        $this->onQueue('certificates');
    }

    public function backoff(): array
    {
        return config('medicina_laboral.certificados.download.backoff_seconds');
    }

    public function handle(CertificateAttachmentService $service): void
    {
        try {
            $service->download($this->attachmentId);
        } catch (\Throwable $e) {
            throw new \RuntimeException(in_array($e->getMessage(), ['invalid_size', 'invalid_mime', 'invalid_extension', 'invalid_media_url', 'missing_credentials'], true) ? $e->getMessage() : 'certificate_download_failed');
        }
    }

    public function failed(?\Throwable $error): void
    {
        app(CertificateAttachmentService::class)->fail($this->attachmentId, $error?->getMessage());
    }
}
