<?php
declare(strict_types=1);

namespace Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use App\Provider\Http\HttpDataProvider;
use App\Http\HttpClientInterface;
use App\Parser\JsonDecoder;

final class HttpDataProviderTest extends TestCase
{
    public function testLazySlotsFetching(): void
    {
        $doctors = [['id' => 1, 'name' => 'John']];
        $slots = [['start' => '2025-08-20T10:00:00+00:00', 'end' => '2025-08-20T10:30:00+00:00']];

        $http = $this->createMock(HttpClientInterface::class);

        // Expect doctor list call once
        $http->expects($this->at(0))
            ->method('get')
            ->with('http://api.test', $this->arrayHasKey('Authorization'))
            ->willReturn(json_encode($doctors));

        // Expect slots call only when fetcher executed
        $http->expects($this->at(1))
            ->method('get')
            ->with('http://api.test/1/slots', $this->arrayHasKey('Authorization'))
            ->willReturn(json_encode($slots));

        $decoder = new JsonDecoder();
        $provider = new HttpDataProvider($http, $decoder, 'http://api.test', 'user', 'pass');

        $iter = $provider->getDoctors();
        $this->assertInstanceOf(\Traversable::class, $iter);

        $doctorData = iterator_to_array($iter)[0];
        // No slots call yet; now call getSlots()
        $fetchedSlots = iterator_to_array($doctorData->getSlots());
        $this->assertCount(1, $fetchedSlots);
    }
}
