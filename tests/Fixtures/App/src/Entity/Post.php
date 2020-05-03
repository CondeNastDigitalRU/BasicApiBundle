<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Entity;

use Ramsey\Uuid\UuidInterface;

abstract class Post
{
    /** @var UuidInterface */
    public $id;

    /** @var string */
    public $title;

    /** @var int */
    public $views;
}
