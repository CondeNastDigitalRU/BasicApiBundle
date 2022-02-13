<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\e2e;

use Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Article;
use Condenast\BasicApiBundle\Tests\Fixtures\App\DTO\Tag;

final class ObjectMother
{
    public static function alpacaArticle(): Article
    {
        $tag1 = new Tag();
        $tag1->name = 'Animals';
        $tag1->slug = 'animals';

        $tag2 = new Tag();
        $tag2->name = 'Alpaca';
        $tag2->slug = 'alpaca';

        $article = new Article();
        $article->id = 1;
        $article->title = 'Alpacas are amazing';
        $article->tags = [$tag1, $tag2];

        return $article;
    }

    private static function alpacaArticleNormalized(): array
    {
        return [
            'id' => 1,
            'title' => 'Alpacas are amazing',
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
        ];
    }

    public static function alpacaArticleJson(): string
    {
        return \json_encode(self::alpacaArticleNormalized());
    }

    public static function llamaArticle(): Article
    {
        $tag1 = new Tag();
        $tag1->name = 'Animals';
        $tag1->slug = 'animals';

        $tag2 = new Tag();
        $tag2->name = 'Llama';
        $tag2->slug = 'llama';

        $article = new Article();
        $article->id = 2;
        $article->title = 'Llamas are awesome';
        $article->tags = [$tag1, $tag2];

        return $article;
    }

    public static function llamaArticleNormalized(): array
    {
        return [
            'id' => 2,
            'title' => 'Llamas are awesome',
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
        ];
    }

    public static function articles(): array
    {
        return [self::alpacaArticle(), self::llamaArticle()];
    }

    public static function articlesJson(): string
    {
        return \json_encode([self::alpacaArticleNormalized(), self::llamaArticleNormalized()]);
    }

    private static function invalidArticleNormalized(): array
    {
        return ['title' => '', 'tags' => []];
    }

    public static function invalidArticleJson(): string
    {
        return \json_encode(self::invalidArticleNormalized());
    }

    public static function invalidArticlesJson(): string
    {
        return \json_encode([self::invalidArticleNormalized(), self::invalidArticleNormalized()]);
    }
}
