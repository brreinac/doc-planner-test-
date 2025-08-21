<?php
declare(strict_types=1);

namespace App\Http;

use App\Exceptions\HttpException;

interface HttpClientInterface
{
    /**
     * Perform GET and return response body.
     *
     * @throws HttpException on non-recoverable network error
     */
    public function get(string $url, array $headers = []): string;
}