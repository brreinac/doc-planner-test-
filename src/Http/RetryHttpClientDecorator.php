<?php
declare(strict_types=1);

namespace App\Http;

use App\Exceptions\HttpException;

class RetryHttpClientDecorator implements HttpClientInterface
{
    private HttpClientInterface $inner;
    private int $maxAttempts;
    private int $delayMs;

    public function __construct(HttpClientInterface $inner, int $maxAttempts = 3, int $delayMs = 100)
    {
        $this->inner = $inner;
        $this->maxAttempts = max(1, $maxAttempts);
        $this->delayMs = max(0, $delayMs);
    }

    public function get(string $url, array $headers = []): string
    {
        $attempt = 0;
        do {
            try {
                return $this->inner->get($url, $headers);
            } catch (HttpException $e) {
                $attempt++;
                if ($attempt >= $this->maxAttempts) {
                    throw $e;
                }
                // small sleep/backoff (blocking) — tests may want to mock delay
                if ($this->delayMs > 0) {
                    usleep((int)$this->delayMs * 1000);
                }
            }
        } while ($attempt < $this->maxAttempts);

        // unreachable
        throw new HttpException("Failed after {$this->maxAttempts} attempts for {$url}");
    }
}
