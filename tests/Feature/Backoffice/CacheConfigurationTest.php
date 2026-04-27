<?php

namespace Tests\Feature\Backoffice;

use Tests\TestCase;

class CacheConfigurationTest extends TestCase
{
    public function test_testing_runtime_uses_array_cache_store(): void
    {
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.stores.array.driver'));
    }

    public function test_file_cache_store_is_configured_for_local_runtime(): void
    {
        $this->assertSame('file', config('cache.stores.file.driver'));
        $this->assertSame('database', config('cache.stores.database.driver'));
    }
}
