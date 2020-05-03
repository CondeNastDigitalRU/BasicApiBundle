<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiDocTest extends WebTestCase
{
    public function testApiDoc(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/doc.json');
        $response = $client->getResponse();

        $this->assertJsonStringEqualsJsonString(
            \file_get_contents(\dirname(__DIR__).'/Fixtures/apiDoc.json'),
            $response->getContent()
        );
    }
}
