<?php

namespace App\Services\Certificates;

class CertificatePolicy
{
    public function snapshot(): array
    {
        $c = config('medicina_laboral.certificados');
        if ((int) $c['max_size_kb'] < 1 || (int) $c['max_files'] < 1) {
            throw new \InvalidArgumentException('Invalid certificate limits');
        }

        return ['max_bytes' => (int) $c['max_size_kb'] * 1024, 'max_files' => (int) $c['max_files'],
            'mime_types' => $c['allowed_mime_types'], 'extensions' => $c['allowed_extensions']];
    }

    public function validate(string $bytes, array $policy, ?string $filename): string
    {
        if ($bytes === '' || strlen($bytes) > $policy['max_bytes']) {
            throw new \RuntimeException('invalid_size');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! in_array($mime, $policy['mime_types'], true)) {
            throw new \RuntimeException('invalid_mime');
        }
        if ($filename && ! in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $policy['extensions'], true)) {
            throw new \RuntimeException('invalid_extension');
        }

        return $mime;
    }
}
