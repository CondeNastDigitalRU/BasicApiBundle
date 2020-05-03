<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Controller;

use Condenast\BasicApiBundle\Annotation as Api;
use Condenast\BasicApiBundle\Response\ApiResponse;
use Condenast\BasicApiBundle\Tests\Fixtures\App\Entity\Article;
use Condenast\BasicApiBundle\Tests\Fixtures\App\Entity\Tag;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TestController
{
    /**
     * Get article
     *
     * @Route(
     *     "/articles/{id}",
     *     name="app.articles.get",
     *     methods={"GET"},
     *     requirements={"id": "\d+"}
     * )
     * @Api\Action(
     *     resourceName="Article",
     *     response=@Api\Response(
     *         type=Article::class,
     *         context={"groups": "article.detail"}
     *     )
     * )
     */
    public function getArticle(): ApiResponse
    {
        return new ApiResponse($this->createAlpacaArticle());
    }

    /**
     * Get articles
     *
     * @Route(
     *     "/articles",
     *     name="app.articles.cget",
     *     methods={"GET"}
     * )
     * @Api\Action(
     *     resourceName="Article",
     *     response=@Api\Response(
     *         type="Condenast\BasicApiBundle\Tests\Fixtures\App\Entity\Article[]",
     *         context={"groups": "article.list"}
     *     )
     * )
     */
    public function getArticles(): array
    {
        return [
            $this->createAlpacaArticle(),
            $this->createLlamaArticle(),
        ];
    }

    /**
     * Create article
     *
     * @Route(
     *     "/articles",
     *     name="app.articles.post",
     *     methods={"POST"}
     * )
     * @Api\Action(
     *     resourceName="Article",
     *     request=@Api\Request(
     *         argument="article",
     *         type=Article::class,
     *         context={
     *             "groups": "article.write",
     *         },
     *         validation=@Api\Validation(groups={"article.update"})
     *     ),
     *     response=@Api\Response(
     *         type=Article::class,
     *         context={"groups": "article.detail"},
     *         statusCode=201
     *     )
     * )
     */
    public function postArticle(Article $article): Article
    {
        return $article;
    }

    /**
     * Create articles
     *
     * @Route(
     *     "/articles/batch",
     *     name="app.articles.post.batch",
     *     methods={"POST"}
     * )
     * @Api\Action(
     *     resourceName="Article",
     *     request=@Api\Request(
     *         argument="articles",
     *         type="Condenast\BasicApiBundle\Tests\Fixtures\App\Entity\Article[]",
     *         context={
     *             "groups": "article.write",
     *         },
     *         validation=@Api\Validation(groups={"article.update"})
     *     ),
     *     response=@Api\Response(
     *         type="Condenast\BasicApiBundle\Tests\Fixtures\App\Entity\Article[]",
     *         context={"groups": "article.detail"},
     *         statusCode=201
     *     )
     * )
     * @param Article[] $articles
     * @return Article[]
     */
    public function postArticleBatch(array $articles): array
    {
        return $articles;
    }

    /**
     * Throw exception
     *
     * @Route(
     *     "/exception",
     *     name="app.exception",
     *     methods={"GET"}
     * )
     * @Api\Action(
     *     resourceName="Exception"
     * )
     */
    public function throwException(): void
    {
        throw new \Exception('This is an exception that was thrown from the controller');
    }

    /**
     * Throw http exception
     *
     * @Route(
     *     "/http_exception",
     *     name="app.http_exception",
     *     methods={"GET"}
     * )
     * @Api\Action(
     *     resourceName="Exception"
     * )
     */
    public function throwHttpException(): void
    {
        throw new MethodNotAllowedHttpException([], 'This is an http exception that was thrown from the controller');
    }

    /**
     * Not json response
     *
     * @Route(
     *     "/not_json",
     *     name="app.not_json",
     *     methods={"GET"}
     * )
     * @Api\Action(
     *     resourceName="Not JSON"
     * )
     */
    public function notJsonResponse(): Response
    {
        return new Response('OK', 201);
    }

    /**
     * Empty api response
     *
     * @Route(
     *     "/empty_api",
     *     name="app.empty_api",
     *     methods={"GET"}
     * )
     * @Api\Action(
     *     resourceName="Emtpty api",
     *     response=@Api\Response(
     *         statusCode=404
     *     )
     * )
     */
    public function emptyApiResponse(): Response
    {
        return new ApiResponse();
    }

    /**
     * Null response
     *
     * @Route(
     *     "/null",
     *     name="app.null",
     *     methods={"GET"}
     * )
     * @Api\Action(
     *     resourceName="Null",
     *     response=@Api\Response(
     *         statusCode=404
     *     )
     * )
     */
    public function nullResponse()
    {
        return null;
    }

    /**
     * Void response
     *
     * @Route(
     *     "/void",
     *     name="app.void",
     *     methods={"GET"}
     * )
     * @Api\Action(
     *     resourceName="Void",
     *     response=@Api\Response(
     *         statusCode=404
     *     )
     * )
     */
    public function voidResponse(): void
    {
    }

    private function createAlpacaArticle(): Article
    {
        $tag1 = new Tag();
        $tag1->name = 'Animals';
        $tag1->slug = 'animals';

        $tag2 = new Tag();
        $tag2->name = 'Alpaca';
        $tag2->slug = 'alpaca';

        $article = new Article();
        $article->id = Uuid::fromString('a117aca5-a117-aca5-a117-aca5a117aca5');
        $article->title = 'Alpacas are amazing';
        $article->headline = 'Alpacas are the best';
        $article->content = 'Something interesting about alpacas';
        $article->views = 47;
        $article->tags = [$tag1, $tag2];

        return $article;
    }

    private function createLlamaArticle(): Article
    {
        $tag1 = new Tag();
        $tag1->name = 'Animals';
        $tag1->slug = 'animals';

        $tag2 = new Tag();
        $tag2->name = 'Llama';
        $tag2->slug = 'llama';

        $article = new Article();
        $article->id = Uuid::fromString('11a111a5-11a1-11a5-11a1-11a511a111a5');
        $article->title = 'Llamas are awesome';
        $article->headline = 'Llamas are good, but alpacas are the best';
        $article->content = 'Something interesting about llamas';
        $article->views = 17;
        $article->tags = [$tag1, $tag2];

        return $article;
    }
}
