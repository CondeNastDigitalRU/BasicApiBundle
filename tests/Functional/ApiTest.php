<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiTest extends WebTestCase
{
    use Assert;
    use Client;

    /**
     * @test
     */
    public function it_can_fetch_query_parameters(): void
    {
        $response = self::request('GET', '/api/query_params');

        self::assertJsonResponse($response, 200);
        self::assertJsonStringEqualsJsonString(
            \json_encode([
                'string' => 'default',
                'strings' => ['default'],
                'int' => 10,
                'ints' => [10],
                'sorting' => 'ASC',
                'sortings' => ['id' => 'ASC'],
            ]),
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize_an_object(): void
    {
        $response = self::request('GET', '/api/articles/1');

        self::assertJsonResponse($response, 200);
        self::assertJsonStringEqualsJsonString(ObjectMother::alpacaArticleJson(), $response->getContent());
    }

    /**
     * @test
     */
    public function it_can_serialize_an_array_of_objects(): void
    {
        $response = self::request('GET', '/api/articles');

        self::assertJsonResponse($response, 200);
        self::assertJsonStringEqualsJsonString(ObjectMother::articlesJson(), $response->getContent());
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_request_into_an_object(): void
    {
        $response = self::request('POST', '/api/articles', ObjectMother::alpacaArticleJson());

        self::assertJsonResponse($response, 201);
        self::assertJsonStringEqualsJsonString(ObjectMother::alpacaArticleJson(), $response->getContent());
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_request_into_an_array_of_objects(): void
    {
        $response = self::request('POST', '/api/articles/batch', ObjectMother::articlesJson());

        self::assertJsonResponse($response, 201);
        self::assertJsonStringEqualsJsonString(ObjectMother::articlesJson(), $response->getContent());
    }

    /**
     * @test
     */
    public function it_can_validate_a_deserialized_object(): void
    {
        $response = self::request('POST', '/api/articles', ObjectMother::invalidArticleJson());

        self::assertJsonValidationResponse($response, 2);
    }

    /**
     * @test
     */
    public function it_can_validate_a_deserialized_array_of_objects(): void
    {
        $response = self::request('POST', '/api/articles/batch', ObjectMother::invalidArticlesJson());

        self::assertJsonValidationResponse($response, 4);
    }

    /**
     * @test
     */
    public function it_responds_with_an_error_if_the_format_is_not_json(): void
    {
        $response = self::request('POST', '/api/articles', '<xml></xml>', 'application/xml');

        self::assertJsonExceptionResponse($response, 415);
    }

    /**
     * @test
     */
    public function it_responds_with_an_error_if_the_json_is_malformed(): void
    {
        $response = self::request('POST', '/api/articles', 'malformed json');

        self::assertJsonExceptionResponse($response, 400);
    }

    /**
     * @test
     */
    public function it_can_serialize_an_exception(): void
    {
        $response = self::request('GET', '/api/exception');

        self::assertJsonExceptionResponse($response, 500);
    }

    /**
     * @test
     */
    public function it_can_serialize_a_http_exception(): void
    {
        $response = self::request('GET', '/api/http_exception');

        self::assertJsonExceptionResponse($response, 403);
    }

    /**
     * @test
     */
    public function it_responds_with_the_empty_string_if_the_payload_data_is_null(): void
    {
        $response = self::request('GET', '/api/empty_payload');

        self::assertSame(204, $response->getStatusCode());
        self::assertNull($response->headers->get('Content-Type'));
        self::assertSame('', $response->getContent());
    }

    /**
     * @test
     */
    public function it_can_work_with_a_invokable_controller(): void
    {
        $response = self::request('GET', '/api/articles/best');

        self::assertJsonResponse($response, 200);
        self::assertJsonStringEqualsJsonString(ObjectMother::alpacaArticleJson(), $response->getContent());
    }

    /**
     * @test
     */
    public function a_custom_header_can_be_added_to_a_payload(): void
    {
        $response = self::request('GET', '/api/articles/1');

        self::assertSame('Value', $response->headers->get('Awesome-Header'));
    }
}
