<?php
declare(strict_types=1);

namespace App\Provider\Static;

use RuntimeException;

class LocalFileReader implements FileReaderInterface
{
    public function read(string $path): string
    {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: {$path}");
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Unable to read file: {$path}");
        }
        return $content;
    }
}
