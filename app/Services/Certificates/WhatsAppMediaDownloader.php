<?php

namespace App\Services\Certificates;

use Illuminate\Support\Facades\Http;

class WhatsAppMediaDownloader
{
    public function download(string $id, int $limit): string
    {
        $token = config('medicina_laboral.whatsapp.token');
        if (! $token) {
            throw new \RuntimeException('missing_credentials');
        }
        $options = ['allow_redirects' => false];
        $proxy = config('medicina_laboral.whatsapp.proxy_unlu');
        if ($proxy) {
            $options['proxy'] = $proxy;
        }
        $client = fn () => Http::withToken($token)->connectTimeout(10)
            ->timeout((int) config('medicina_laboral.certificados.download.timeout_seconds'))->withOptions($options);
        $info = $client()->get('https://graph.facebook.com/v21.0/'.rawurlencode($id))->throw()->json();
        $url = $info['url'] ?? '';
        $host = parse_url($url, PHP_URL_HOST);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! is_string($host)
            || ! collect(config('medicina_laboral.certificados.download.media_hosts'))->contains(
                fn ($allowed) => $host === $allowed || str_ends_with($host, '.'.$allowed))) {
            throw new \RuntimeException('invalid_media_url');
        }
        if (isset($info['file_size']) && $info['file_size'] > $limit) {
            throw new \RuntimeException('invalid_size');
        }
        $file = tmpfile();
        if ($file === false) {
            throw new \RuntimeException('temporary_storage_failed');
        }
        try {
            $response = $client()->withOptions([
                'sink' => $file,
                'progress' => function ($total, $downloaded) use ($limit) {
                    if ($downloaded > $limit || $total > $limit) {
                        throw new \RuntimeException('invalid_size');
                    }
                },
            ])->get($url)->throw();
            rewind($file);
            $bytes = stream_get_contents($file, $limit + 1);
            if (strlen($bytes) > $limit) {
                throw new \RuntimeException('invalid_size');
            }

            return $bytes;
        } finally {
            fclose($file);
        }
    }
}
