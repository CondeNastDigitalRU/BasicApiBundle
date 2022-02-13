<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Controller;

use Condenast\BasicApiBundle\Attribute as Api;
use Condenast\BasicApiBundle\Request\QueryParamBag;
use Condenast\BasicApiBundle\Response\Payload;
use Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Article;
use Condenast\BasicApiBundle\Tests\e2e\ObjectMother;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

class ApiController
{
    /**
     * Query params
     */
    #[Route(path: "/query_params", name: "app.query_params", methods: ["GET"])]
    #[Api\Resource("Query params")]
    #[Api\QueryParam(
        name: "id",
        path: "filter.id",
        constraints: [
            new Assert\GreaterThan(0),
            new Assert\LessThan(100),
            new Assert\Regex("/\d+/"),
        ],
        default: 1,
        description: "Id",
    )]
    #[Api\QueryParam(
        name: "ids",
        path: "filter.ids",
        isArray: true,
        constraints: [
            new Assert\GreaterThan(0),
        ],
        default: [1],
        description: "Ids",
    )]
    #[Api\QueryParam(
        name: "sorting_id",
        path: "sorting.id",
        constraints: [
            new Assert\Choice(["ASC", "DESC"]),
        ],
        default: "ASC",
        description: "Sort by id",
    )]
    #[Api\QueryParam(
        name: "email",
        constraints: [
            new Assert\Email(),
        ],
        default: null,
        description: "Email",
    )]
    public function queryParams(QueryParamBag $query): Payload
    {
        return new Payload($query->all());
    }

    /**
     * Get article
     *
     * @OA\Response(
     *     response=200,
     *     description="Article",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Nelmio\Model(type=Article::class, groups={"article.read"})
     *     )
     * )
     */
    #[Route(path: "/articles/{id}", name: "app.articles.get", requirements: ["id" => "\d+"], methods: ["GET"])]
    #[Api\Resource("Article")]
    public function getArticle(): Payload
    {
        return new Payload(ObjectMother::alpacaArticle(), 200, ['groups' => 'article.read'], ['Awesome-Header' => 'Value']);
    }

    /**
     * Get articles
     *
     * @OA\Response(
     *     response=200,
     *     description="Articles",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Nelmio\Model(type=Article::class, groups={"article.read"}))
     *     )
     * )
     */
    #[Route(path: "/articles", name: "app.articles.cget", methods: ["GET"])]
    #[Api\Resource("Article")]
    public function getArticles(): Payload
    {
        return new Payload(ObjectMother::articles(), 200, ['groups' => 'article.read']);
    }

    /**
     * Create article
     *
     * @OA\Response(
     *     response=201,
     *     description="Created article",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Nelmio\Model(type=Article::class, groups={"article.read"})
     *     )
     * )
     */
    #[Route(path: "/articles", name: "app.articles.post", methods: ["POST"])]
    #[Api\Resource("Article")]
    #[Api\Deserialization(argument: "article", type: Article::class, context: ["groups" => "article.write"])]
    #[Api\Validation(groups: ["article.write"])]
    public function postArticle(Article $article): Payload
    {
        return new Payload($article, 201, ['groups' => 'article.read']);
    }

    /**
     * Create articles
     *
     * @OA\Response(
     *     response=200,
     *     description="Articles",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Nelmio\Model(type=Article::class, groups={"article.read"}))
     *     )
     * )
     * @param list<Article> $articles
     */
    #[Route(path: "/articles/batch", name: "app.articles.post.batch", methods: ["POST"])]
    #[Api\Resource("Article")]
    #[Api\Deserialization(argument: "articles", type: "Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Article[]", context: ["groups" => "article.write"])]
    #[Api\Validation(groups: ["article.write"])]
    public function postArticleBatch(array $articles): Payload
    {
        return new Payload($articles, 201, ['groups' => 'article.read']);
    }

    /**
     * Throw exception
     */
    #[Route(path: "/exception", name: "app.exception", methods: ["GET"])]
    #[Api\Resource("Exception")]
    public function throwException(): void
    {
        throw new \RuntimeException('Message');
    }

    /**
     * Throw http exception
     */
    #[Route(path: "/http_exception", name: "app.http_exception", methods: ["GET"])]
    #[Api\Resource("Exception")]
    public function throwHttpException(): void
    {
        throw new AccessDeniedHttpException('Access denied');
    }

    /**
     * Empty payload
     */
    #[Route(path: "/empty_payload", name: "app.empty_payload", methods: ["GET"])]
    #[Api\Resource("Empty payload")]
    public function emptyPayload(): Payload
    {
        return new Payload(null, 204);
    }
}
