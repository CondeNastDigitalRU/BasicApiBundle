<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

trait Client
{
    private static function request(string $method, string $uri, string $content = '', string $contentType = null): Response
    {
        $contentType = '' !== $content ? $contentType ?? 'application/json' : null;
        $server = null !== $contentType ? ['CONTENT_TYPE' => $contentType] : [];

        $client = self::createClient();
        $client->request($method, $uri, [], [], $server, $content);

        return $client->getResponse();
    }
}
