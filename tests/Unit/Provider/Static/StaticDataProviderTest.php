<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Provider\Static\StaticDataProvider;
use App\Provider\Static\FileReaderInterface;
use App\Parser\JsonDecoder;

final class FakeReader implements FileReaderInterface
{
    private string $content;
    public function __construct(string $content) { $this->content = $content; }
    public function read(string $path): string { return $this->content; }
}

final class StaticDataProviderTest extends TestCase
{
    public function testGetDataReturnsDecodedArray(): void
    {
        $json = json_encode([
            ['id' => 1, 'name' => 'john doe', 'slots' => []]
        ]);

        $reader = new FakeReader($json);
        $decoder = new JsonDecoder();
        $provider = new StaticDataProvider($reader, $decoder, '/any/path');

        $data = $provider->getData();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertSame(1, $data[0]['id']);
    }

    public function testGetDataOnInvalidJsonThrows(): void
    {
        $this->expectException(\App\Exceptions\JsonDecodeException::class);

        $reader = new FakeReader('invalid json');
        $decoder = new JsonDecoder();
        $provider = new StaticDataProvider($reader, $decoder, '/any/path');

        $provider->getData();
    }
}
