<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

trait Assert
{
    private static function assertJsonResponse(Response $response, int $statusCode): void
    {
        self::assertSame($statusCode, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson($response->getContent());
    }

    private static function assertJsonValidationResponse(Response $response, int $violationsCount): void
    {
        self::assertJsonResponse($response, 400);
        $content = \json_decode($response->getContent(), true);
        self::assertArrayHasKey('type', $content);
        self::assertArrayHasKey('title', $content);
        self::assertArrayHasKey('detail', $content);
        self::assertArrayHasKey('violations', $content);
        self::assertCount($violationsCount, $content['violations']);
    }

    private static function assertJsonExceptionResponse(Response $response, int $statusCode): void
    {
        self::assertJsonResponse($response, $statusCode);
        $content = \json_decode($response->getContent(), true);
        self::assertArrayHasKey('type', $content);
        self::assertArrayHasKey('title', $content);
        self::assertArrayHasKey('status', $content);
        self::assertArrayHasKey('detail', $content);
        self::assertArrayHasKey('class', $content);
    }
}
