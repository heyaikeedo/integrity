<?php

declare(strict_types=1);

namespace Aikeedo\Integrity;

use DateTimeImmutable;
use Easy\Router\Mapper\SimpleMapper;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\Psr18Client;
use Throwable;

class SystemIntegrityManager
{
    public function __construct(
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

        $cache = new FilesystemAdapter();
        $item = $cache->getItem('iam');
        if ($item->isHit()) {
            return;
        }

        $item->expiresAt(new DateTimeImmutable('+7 days'));
        $cache->save($item);

        $client = new Psr18Client();
        $client = $client->withOptions(['timeout' => 10]);

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

            $client->sendRequest($req);
        } catch (Throwable) {
        }
    }
}
