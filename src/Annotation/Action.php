<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("resourceName", type="string"),
 *     @Attribute("request", type="Condenast\BasicApiBundle\Annotation\Request"),
 *     @Attribute("response", type="Condenast\BasicApiBundle\Annotation\Response")
 * })
 */
class Action
{
    /** @var string|null */
    private $resourceName;

    /** @var Request|null */
    private $request;

    /** @var Response|null */
    private $response;

    public function __construct(array $values)
    {
        $this->resourceName = $values['resourceName'] ?? null;
        $this->request = $values['request'] ?? null;
        $this->response = $values['response'] ?? null;
    }

    public function getResourceName(): ?string
    {
        return $this->resourceName;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
