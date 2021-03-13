<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\DTO;

class Article
{
    /** @var int|null */
    public $id;

    /** @var string|null */
    public $title;

    /** @var Tag[]|null */
    public $tags;
}
