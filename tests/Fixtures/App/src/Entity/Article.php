<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Entity;

class Article extends Post
{
    /** @var string */
    public $headline;

    /** @var string */
    public $content;

    /** @var Tag[] */
    public $tags;
}
