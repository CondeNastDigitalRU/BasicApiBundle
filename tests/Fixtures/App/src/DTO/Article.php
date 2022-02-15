<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\DTO;

use OpenApi\Annotations as OA;

class Article
{
    public ?int $id = null;
    public ?string $title = null;

    /**
     * @OA\Property(type="array", @OA\Items(type="string"))
     */
    public array $tags = [];
}
