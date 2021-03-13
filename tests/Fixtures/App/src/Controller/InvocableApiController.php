<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Controller;

use Condenast\BasicApiBundle\Annotation as Api;
use Condenast\BasicApiBundle\Response\Payload;
use Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Article;
use Condenast\BasicApiBundle\Tests\Functional\ObjectMother;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

class InvocableApiController
{
    /**
     * Get best article
     *
     * @Route(
     *     "/articles/best",
     *     name="app.articles.best",
     *     methods={"GET"},
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
    public function __invoke(): Payload
    {
        return new Payload(ObjectMother::alpacaArticle(), 200, ['groups' => 'article.read']);
    }
}
