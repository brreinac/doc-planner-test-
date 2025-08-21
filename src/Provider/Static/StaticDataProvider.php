<?php
declare(strict_types=1);

namespace App\Provider\Static;

use App\Provider\DataProviderInterface;
use App\DTO\DoctorData;
use App\Parser\JsonDecoder;
use App\Provider\FileReaderInterface;

class StaticDataProvider implements DataProviderInterface
{
    private FileReaderInterface $reader;
    private JsonDecoder $decoder;
    private string $path;

    public function __construct(FileReaderInterface $reader, JsonDecoder $decoder, string $path)
    {
        $this->reader = $reader;
        $this->decoder = $decoder;
        $this->path = $path;
    }

    public function getDoctors(): iterable
    {
        $raw = $this->reader->read($this->path);
        $data = $this->decoder->decode($raw);

        foreach ($data as $d) {
            $id = isset($d['id']) ? (int)$d['id'] : null;
            $name = isset($d['name']) ? (string)$d['name'] : '';
            if ($id === null) continue;

            // fetcher returning the slots inline (already decoded)
            $slots = isset($d['slots']) && is_array($d['slots']) ? $d['slots'] : [];
            $fetcher = function() use ($slots) { return $slots; };

            yield new DoctorData($id, $name, $fetcher);
        }
    }
}
