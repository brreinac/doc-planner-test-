<?php
declare(strict_types=1);

namespace App\Provider\Http;

use App\Provider\DataProviderInterface;
use App\DTO\DoctorData;
use App\Http\HttpClientInterface;
use App\Parser\JsonDecoder;
use App\Exceptions\HttpException;
use App\Exceptions\JsonDecodeException;

/**
 * Fetches doctors list from an HTTP endpoint; per-doctor slots are fetched lazily
 * via the DoctorData->getSlots() callable.
 */
class HttpDataProvider implements DataProviderInterface
{
    private HttpClientInterface $http;
    private JsonDecoder $decoder;
    private string $endpoint;
    private string $authHeader;

    public function __construct(HttpClientInterface $http, JsonDecoder $decoder, string $endpoint, string $username, string $password)
    {
        $this->http = $http;
        $this->decoder = $decoder;
        $this->endpoint = rtrim($endpoint, '/');
        $this->authHeader = 'Basic ' . base64_encode($username . ':' . $password);
    }

    public function getDoctors(): iterable
    {
        $body = $this->http->get($this->endpoint, ['Authorization' => $this->authHeader]);
        $doctors = $this->decoder->decode($body);

        foreach ($doctors as $d) {
            $id = isset($d['id']) ? (int)$d['id'] : null;
            $name = isset($d['name']) ? (string)$d['name'] : '';

            if ($id === null) {
                // skip malformed entries
                continue;
            }

            // lazy slots fetcher: when invoked will call HTTP /{id}/slots
            $fetcher = function() use ($id) {
                $body = $this->http->get($this->endpoint . '/' . $id . '/slots', ['Authorization' => $this->authHeader]);
                return $this->decoder->decode($body);
            };

            yield new DoctorData($id, $name, $fetcher);
        }
    }
}
