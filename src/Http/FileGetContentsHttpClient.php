<?php
declare(strict_types=1);

namespace App\Http;

use App\Exceptions\HttpException;

class FileGetContentsHttpClient implements HttpClientInterface
{
    public function get(string $url, array $headers = []): string
    {
        $context = stream_context_create(['http' => ['header' => $this->formatHeaders($headers)]]);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            throw new HttpException("HTTP GET failed for {$url}");
        }
        return $result;
    }

    private function formatHeaders(array $headers): string
    {
        $lines = [];
        foreach ($headers as $k => $v) {
            $lines[] = "{$k}: {$v}";
        }
        return implode("\r\n", $lines);
    }
}
