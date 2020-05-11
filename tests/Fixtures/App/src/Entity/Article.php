<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Entity;

class Article extends Post
{
    /** @var string|null */
    public $headline;

    /** @var string|null */
    public $content;

    /** @var Tag[]|null */
    public $tags;
}
