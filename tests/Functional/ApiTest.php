<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiTest extends WebTestCase
{
    public function testObjectSerialization(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/articles/1');
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 200);
        $this->assertJsonStringEqualsJsonString(
            \json_encode([
                'id' => 'a117aca5-a117-aca5-a117-aca5a117aca5',
                'title' => 'Alpacas are amazing',
                'headline' => 'Alpacas are the best',
                'content' => 'Something interesting about alpacas',
                'tags' => [
                    [
                        'name' => 'Animals',
                        'slug' => 'animals',
                    ],
                    [
                        'name' => 'Alpaca',
                        'slug' => 'alpaca',
                    ]
                ],
                'views' => 47,
            ]),
            $response->getContent()
        );
    }

    public function testObjectCollectionSerialization(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/articles');
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 200);
        $this->assertJsonStringEqualsJsonString(
            \json_encode(
                [
                    [
                        'id' => 'a117aca5-a117-aca5-a117-aca5a117aca5',
                        'title' => 'Alpacas are amazing',
                        'headline' => 'Alpacas are the best',
                        'views' => 47,
                    ],
                    [
                        'id' => '11a111a5-11a1-11a5-11a1-11a511a111a5',
                        'title' => 'Llamas are awesome',
                        'headline' => 'Llamas are good, but alpacas are the best',
                        'views' => 17,
                    ]
                ]
            ),
            $response->getContent()
        );
    }

    public function testObjectDeserialization(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/articles',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            \json_encode([
                'id' => 'a117aca5-a117-aca5-a117-aca5a117aca5',
                'title' => 'Alpacas are amazing',
                'headline' => 'Alpacas are the best',
                'content' => 'Something interesting about alpacas',
                'tags' => [
                    [
                        'name' => 'Animals',
                        'slug' => 'animals',
                    ],
                    [
                        'name' => 'Alpaca',
                        'slug' => 'alpaca',
                    ]
                ],
            ])
        );
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 201);
        $this->assertJsonStringEqualsJsonString(
            \json_encode([
                'id' => 'a117aca5-a117-aca5-a117-aca5a117aca5',
                'title' => 'Alpacas are amazing',
                'headline' => 'Alpacas are the best',
                'content' => 'Something interesting about alpacas',
                'tags' => [
                    [
                        'name' => 'Animals',
                        'slug' => 'animals',
                    ],
                    [
                        'name' => 'Alpaca',
                        'slug' => 'alpaca',
                    ]
                ],
                'views' => null,
            ]),
            $response->getContent()
        );
    }

    public function testObjectCollectionDeserialization(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/articles/batch',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            \json_encode([
                [
                    'id' => 'a117aca5-a117-aca5-a117-aca5a117aca5',
                    'title' => 'Alpacas are amazing',
                    'headline' => 'Alpacas are the best',
                    'content' => 'Something interesting about alpacas',
                    'tags' => [
                        [
                            'name' => 'Animals',
                            'slug' => 'animals',
                        ],
                        [
                            'name' => 'Alpaca',
                            'slug' => 'alpaca',
                        ]
                    ],
                ],
                [
                    'title' => 'Llamas are awesome',
                    'headline' => 'Llamas are good, but alpacas are the best',
                    'content' => 'Something interesting about llamas',
                    'tags' => [
                        [
                            'name' => 'Animals',
                            'slug' => 'animals',
                        ],
                        [
                            'name' => 'Llama',
                            'slug' => 'llama',
                        ]
                    ],
                ]
            ])
        );
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 201);
        $this->assertJsonStringEqualsJsonString(
            \json_encode(
                [
                    [
                        'id' => 'a117aca5-a117-aca5-a117-aca5a117aca5',
                        'title' => 'Alpacas are amazing',
                        'headline' => 'Alpacas are the best',
                        'content' => 'Something interesting about alpacas',
                        'tags' => [
                            [
                                'name' => 'Animals',
                                'slug' => 'animals',
                            ],
                            [
                                'name' => 'Alpaca',
                                'slug' => 'alpaca',
                            ]
                        ],
                        'views' => null,
                    ],
                    [
                        'id' => null,
                        'title' => 'Llamas are awesome',
                        'headline' => 'Llamas are good, but alpacas are the best',
                        'content' => 'Something interesting about llamas',
                        'tags' => [
                            [
                                'name' => 'Animals',
                                'slug' => 'animals',
                            ],
                            [
                                'name' => 'Llama',
                                'slug' => 'llama',
                            ]
                        ],
                        'views' => null,
                    ]
                ]
            ),
            $response->getContent()
        );
    }

    public function testObjectValidation(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/articles',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            \json_encode([
                'title' => 'Al',
                'tags' => [
                    [
                        'name' => '',
                        'slug' => 'alpaca',
                    ]
                ],
            ])
        );
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 400);
        $violations = \json_decode($response->getContent(), true);

        $this->assertCount(3, $violations['violations']);

        $this->assertEquals('title', $violations['violations'][0]['propertyPath']);
        $this->assertEquals('urn:uuid:9ff3fdc4-b214-49db-8718-39c315e33d45', $violations['violations'][0]['type']);

        $this->assertEquals('tags[0].name', $violations['violations'][1]['propertyPath']);
        $this->assertEquals('urn:uuid:c1051bb4-d103-4f74-8988-acbcafc7fdc3', $violations['violations'][1]['type']);

        $this->assertEquals('tags', $violations['violations'][2]['propertyPath']);
        $this->assertEquals('urn:uuid:bef8e338-6ae5-4caf-b8e2-50e7b0579e69', $violations['violations'][2]['type']);
    }

    public function testObjectCollectionValidation(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/articles/batch',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            \json_encode(
                [
                    [
                        'title' => 'Al',
                        'tags' => [
                            [
                                'name' => '',
                                'slug' => 'alpaca',
                            ]
                        ],
                    ],
                    [
                        'title' => 'L',
                        'tags' => [
                            [
                                'name' => 'Animals',
                                'slug' => 'animals',
                            ],
                            [
                                'name' => 'Llama',
                                'slug' => '',
                            ]
                        ],
                    ]
                ]
            )
        );
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 400);
        $violations = \json_decode($response->getContent(), true);

        $this->assertCount(5, $violations['violations']);

        $this->assertEquals('[0].title', $violations['violations'][0]['propertyPath']);
        $this->assertEquals('urn:uuid:9ff3fdc4-b214-49db-8718-39c315e33d45', $violations['violations'][0]['type']);

        $this->assertEquals('[0].tags[0].name', $violations['violations'][1]['propertyPath']);
        $this->assertEquals('urn:uuid:c1051bb4-d103-4f74-8988-acbcafc7fdc3', $violations['violations'][1]['type']);

        $this->assertEquals('[0].tags', $violations['violations'][2]['propertyPath']);
        $this->assertEquals('urn:uuid:bef8e338-6ae5-4caf-b8e2-50e7b0579e69', $violations['violations'][2]['type']);

        $this->assertEquals('[1].title', $violations['violations'][3]['propertyPath']);
        $this->assertEquals('urn:uuid:9ff3fdc4-b214-49db-8718-39c315e33d45', $violations['violations'][3]['type']);

        $this->assertEquals('[1].tags[1].slug', $violations['violations'][4]['propertyPath']);
        $this->assertEquals('urn:uuid:c1051bb4-d103-4f74-8988-acbcafc7fdc3', $violations['violations'][4]['type']);
    }

    public function testBadRequestFormat(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/articles',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/xml',
            ],
            \json_encode([])
        );
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 415);
    }

    public function testException(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/exception');
        $response = $client->getResponse();

        $this->assertExceptionJsonResponse($response, 500, 'This is an exception that was thrown from the controller');
    }

    public function testHttpException(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/http_exception');
        $response = $client->getResponse();

        $this->assertExceptionJsonResponse($response, 405, 'This is an http exception that was thrown from the controller');
    }

    public function testNotJsonResponse(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/not_json');
        $response = $client->getResponse();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('OK', $response->getContent());
    }

    public function testEmptyApiResponse(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/empty_api');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertNotEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('', $response->getContent());
    }

    public function testNullResponse(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/null');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertNotEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('', $response->getContent());
    }

    public function testVoidResponse(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/void');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertNotEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals('', $response->getContent());
    }

    public function testInvocableController(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/notes/1');
        $response = $client->getResponse();

        $this->assertJsonResponse($response, 200);
        $this->assertJsonStringEqualsJsonString(
            \json_encode([
                'id' => 'a117aca5-a117-aca5-a117-aca5a117aca5',
                'title' => 'Note about alpacas',
                'text' => 'The alpaca is a species of South American camelid descended from the vicuña',
                'views' => 47,
            ]),
            $response->getContent()
        );
    }

    private function assertExceptionJsonResponse(Response $response, int $statusCode, string $message): void
    {
        $this->assertJsonResponse($response, $statusCode);
        $exception = \json_decode($response->getContent(), true);
        $this->assertIsArray($exception[0] ?? null);
        $this->assertEquals($message, $exception[0]['message']);
        $this->assertIsString($exception[0]['class']);
        $this->assertIsArray($exception[0]['trace']);
    }

    private function assertJsonResponse(Response $response, int $statusCode): void
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }
}
