<?php

declare(strict_types=1);

namespace Aikeedo\Integrity;

use DateTimeImmutable;
use Easy\Router\Mapper\SimpleMapper;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

class SystemIntegrityManager
{
    public function __construct(
        private CacheItemPoolInterface $cache,
        private ClientInterface $client,
        private RequestFactoryInterface $reqfac,
        private StreamFactoryInterface $streamfac,
        private SimpleMapper $mapper,
    ) {
    }

    public function audit(
        string $domain,
        ?string $version = null,
        ?string $license = null
    ): void {
        $this->mapper->map('POST', '/iam', Handler::class);

        $item = $this->cache->getItem('iam');
        if ($item->isHit()) {
            return;
        }

        try {
            $req = $this->reqfac->createRequest('POST', 'https://api.aikeedo.com/iam')
                ->withBody(
                    $this->streamfac->createStream(json_encode([
                        'domain' => $domain,
                        'license' => $license,
                        'version' => $version
                    ]))
                )
                ->withHeader('Content-Type', 'application/json');

            $this->client->sendRequest($req);
        } catch (Throwable) {
        }

        $item->expiresAt(new DateTimeImmutable('+7 days'));
        $this->cache->save($item);
    }
}
