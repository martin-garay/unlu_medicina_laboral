<?php

namespace Tests\Unit\Services\Storage;

use App\Services\Storage\MetadataDraftAttachmentStorage;
use Tests\TestCase;

class MetadataDraftAttachmentStorageTest extends TestCase
{
    public function test_stores_attachment_metadata_for_future_definitive_storage(): void
    {
        config()->set('medicina_laboral.storage.driver', 'metadata');

        $stored = (new MetadataDraftAttachmentStorage())->store([
            'provider_media_id' => 'media-1',
            'mime_type' => 'application/pdf',
            'filename' => 'certificado.pdf',
            'caption' => 'reposo',
        ], 'document');

        $this->assertSame('media-1', $stored->providerMediaId);
        $this->assertSame('application/pdf', $stored->mimeType);
        $this->assertSame('certificado.pdf', $stored->filename);
        $this->assertSame('document', $stored->sourceType);
        $this->assertSame('metadata', $stored->storageDriver);
        $this->assertSame('metadata_only', $stored->storageStatus);
        $this->assertNotSame('', $stored->storedAt);
    }
}
