<?php
declare(strict_types=1);

namespace App\Parser;

use App\Exceptions\JsonDecodeException;

class JsonDecoder
{
    /**
     * Decode JSON string to array (throws on error).
     *
     * @throws JsonDecodeException
     */
    public function decode(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return [];
            }
            return $data;
        } catch (\JsonException $e) {
            throw new JsonDecodeException($e->getMessage(), 0, $e);
        }
    }
}
