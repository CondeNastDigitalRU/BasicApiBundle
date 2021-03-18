<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Controller;

use Condenast\BasicApiBundle\Annotation as Api;
use Condenast\BasicApiBundle\Request\QueryParamBag;
use Condenast\BasicApiBundle\Response\Payload;
use Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Article;
use Condenast\BasicApiBundle\Tests\Functional\ObjectMother;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

class ApiController
{
    /**
     * Query params
     *
     * @Route(
     *     "/query_params",
     *     name="app.query_params",
     *     methods={"GET"},
     * )
     * @Api\Resource("Query params")
     * @Api\QueryParam(name="string", path="filter[string]", type="string", default="default", description="String"),
     * @Api\QueryParam(name="strings", path="filter[strings]", type="string", default={"default"}, map=true, description="Strings")
     * @Api\QueryParam(
     *     name="int",
     *     path="filter[int]",
     *     type="int",
     *     default=10,
     *     constraints={@Assert\LessThanOrEqual(100)},
     *     description="Int",
     * )
     * @Api\QueryParam(
     *     name="ints",
     *     path="filter[ints]",
     *     type="int",
     *     default={10},
     *     constraints={
     *         @Assert\All(
     *             @Assert\LessThanOrEqual(100)
     *         ),
     *         @Assert\Count(max=4)
     *     },
     *     map=true,
     *     description="Ints"
     * )
     * @Api\QueryParam(
     *     name="sorting",
     *     path="soring[id]",
     *     type="string",
     *     default="ASC",
     *     constraints={@Assert\Choice({"ASC", "DESC"})},
     *     description="Sorting by ID"
     * )
     * @Api\QueryParam(
     *     name="sortings",
     *     type="string",
     *     default={"id": "ASC"},
     *     constraints={@Assert\All(@Assert\Choice({"ASC", "DESC"}))},
     *     map=true,
     *     description="Sortings",
     * )
     */
    public function queryParams(QueryParamBag $query): Payload
    {
        return new Payload($query->all());
    }

    /**
     * Get article
     *
     * @Route(
     *     "/articles/{id}",
     *     name="app.articles.get",
     *     methods={"GET"},
     *     requirements={"id": "\d+"}
     * )
     * @Api\Resource("Article")
     * @OA\Response(
     *     response=200,
     *     description="Article",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Nelmio\Model(type=Article::class, groups={"article.read"})
     *     )
     * )
     */
    public function getArticle(): Payload
    {
        return new Payload(ObjectMother::alpacaArticle(), 200, ['groups' => 'article.read'], ['Awesome-Header' => 'Value']);
    }

    /**
     * Get articles
     *
     * @Route(
     *     "/articles",
     *     name="app.articles.cget",
     *     methods={"GET"}
     * )
     * @Api\Resource("Article")
     * @OA\Response(
     *     response=200,
     *     description="Articles",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Nelmio\Model(type=Article::class, groups={"article.read"}))
     *     )
     * )
     */
    public function getArticles(): Payload
    {
        return new Payload(ObjectMother::articles(), 200, ['groups' => 'article.read']);
    }

    /**
     * Create article
     *
     * @Route(
     *     "/articles",
     *     name="app.articles.post",
     *     methods={"POST"}
     * )
     * @Api\Resource("Article")
     * @Api\Deserialization(argument="article", type=Article::class, context={"groups": "article.write"})
     * @Api\Validation(groups={"article.write"})
     * @OA\Response(
     *     response=201,
     *     description="Created article",
     *     @OA\JsonContent(
     *         type="object",
     *         ref=@Nelmio\Model(type=Article::class, groups={"article.read"})
     *     )
     * )
     */
    public function postArticle(Article $article): Payload
    {
        return new Payload($article, 201, ['groups' => 'article.read']);
    }

    /**
     * Create articles
     *
     * @Route(
     *     "/articles/batch",
     *     name="app.articles.post.batch",
     *     methods={"POST"}
     * )
     * @Api\Resource("Article")
     * @Api\Deserialization(
     *     argument="articles",
     *     type="Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Article[]",
     *     context={"groups": "article.write"}
     * )
     * @Api\Validation(groups={"article.write"})
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
    public function postArticleBatch(array $articles): Payload
    {
        return new Payload($articles, 201, ['groups' => 'article.read']);
    }

    /**
     * Throw exception
     *
     * @Route(
     *     "/exception",
     *     name="app.exception",
     *     methods={"GET"}
     * )
     * @Api\Resource("Exception")
     */
    public function throwException(): void
    {
        throw new \RuntimeException('Message');
    }

    /**
     * Throw http exception
     *
     * @Route(
     *     "/http_exception",
     *     name="app.http_exception",
     *     methods={"GET"}
     * )
     * @Api\Resource("Exception")
     */
    public function throwHttpException(): void
    {
        throw new AccessDeniedHttpException('Access denied');
    }

    /**
     * Empty payload
     *
     * @Route(
     *     "/empty_payload",
     *     name="app.empty_payload",
     *     methods={"GET"}
     * )
     * @Api\Resource("Emtpty payload")
     */
    public function emptyPayload(): Payload
    {
        return new Payload(null, 204);
    }
}
