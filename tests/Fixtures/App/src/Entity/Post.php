<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Entity;

use Ramsey\Uuid\UuidInterface;

abstract class Post
{
    /** @var UuidInterface|null */
    public $id;

    /** @var string|null */
    public $title;

    /** @var int|null */
    public $views;
}
