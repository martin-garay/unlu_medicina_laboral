<?php

namespace Tests\Unit\Services\Storage;

use App\Models\AnticipoCertificado;
use App\Models\Conversacion;
use App\Services\Storage\MetadataFinalAttachmentStorage;
use Tests\TestCase;

class MetadataFinalAttachmentStorageTest extends TestCase
{
    public function test_builds_final_attachment_reference_with_configured_disk_and_directory(): void
    {
        config()->set('medicina_laboral.storage.final_driver', 'metadata');
        config()->set('medicina_laboral.storage.final_attachments.disk', 'local');
        config()->set('medicina_laboral.storage.final_attachments.directory', 'medicina_laboral/anticipos');

        $conversation = new Conversacion();
        $conversation->id = 9;

        $anticipo = new AnticipoCertificado();
        $anticipo->id = 15;

        $storage = new MetadataFinalAttachmentStorage();
        $stored = $storage->persist(
            [
                'provider_media_id' => 'media-1',
                'mime_type' => 'application/pdf',
                'filename' => 'certificado.pdf',
                'caption' => 'reposo',
                'source_type' => 'document',
                'stored_at' => '2026-03-20T12:00:00+00:00',
            ],
            $conversation,
            $anticipo,
        );

        $this->assertSame('media-1', $stored->providerFileId);
        $this->assertSame('local', $stored->storageDisk);
        $this->assertSame('medicina_laboral/anticipos/anticipo-15/media-1.pdf', $stored->storagePath);
        $this->assertSame('aceptado', $stored->validationStatus);
        $this->assertSame('metadata', $stored->metadata['storage_driver']);
        $this->assertSame('metadata_only', $stored->metadata['storage_status']);
        $this->assertSame(15, $stored->metadata['anticipo_certificado_id']);
    }
}
