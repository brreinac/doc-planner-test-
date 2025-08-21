<?php
declare(strict_types=1);

namespace App\Provider\Static;

interface FileReaderInterface
{
    /**
     * Read file contents as string.
     *
     * @throws \RuntimeException on failure.
     */
    public function read(string $path): string;
}
