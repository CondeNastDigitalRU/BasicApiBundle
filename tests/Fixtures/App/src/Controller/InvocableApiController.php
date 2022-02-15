<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Controller;

use Condenast\BasicApiBundle\Attribute as Api;
use Condenast\BasicApiBundle\Response\Payload;
use Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Article;
use Condenast\BasicApiBundle\Tests\e2e\ObjectMother;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

class InvocableApiController
{
    /**
     * Get best article
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
    #[Route(path: "/articles/best", name: "app.articles.best", methods: ["GET"])]
    #[Api\Resource("Article")]
    public function __invoke(): Payload
    {
        return new Payload(ObjectMother::alpacaArticle(), 200, ['groups' => 'article.read']);
    }
}
