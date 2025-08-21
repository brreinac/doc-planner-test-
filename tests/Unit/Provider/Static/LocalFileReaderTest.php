<?php
declare(strict_types=1);

namespace Tests\Unit\Provider\Static;

use PHPUnit\Framework\TestCase;
use App\Provider\Static\LocalFileReader;

final class LocalFileReaderTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = sys_get_temp_dir() . '/static_test_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpFile);
    }

    public function testReadReturnsContents(): void
    {
        file_put_contents($this->tmpFile, '{"ok":true}');
        $r = new LocalFileReader();
        $this->assertSame('{"ok":true}', $r->read($this->tmpFile));
    }

    public function testReadThrowsWhenMissing(): void
    {
        $this->expectException(RuntimeException::class);
        (new LocalFileReader())->read('/path/does/not/exist.json');
    }
}
