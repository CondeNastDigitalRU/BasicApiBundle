<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;

/**
 * @Annotation
 * @Target("ANNOTATION")
 * @Attributes({
 *     @Attribute("type", type="string"),
 *     @Attribute("context", type="array"),
 *     @Attribute("statusCode", type="integer")
 * })
 */
class Response
{
    /** @var string|null */
    private $type;

    /** @var array */
    private $context;

    /** @var int */
    private $statusCode;

    public function __construct(array $values)
    {
        $this->type = $values['type'] ?? null;
        $this->context = $values['context'] ?? [];
        $this->statusCode = $values['statusCode'] ?? 200;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
