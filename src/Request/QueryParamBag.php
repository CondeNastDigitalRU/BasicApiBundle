<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Request;

class QueryParamBag
{
    /** @var array<string, mixed> */
    private $params;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function get(string $name)
    {
        if (!\array_key_exists($name, $this->params)) {
            throw new \RuntimeException(\sprintf(
                'Unknown parameter "%s", consider declaring it with the QueryParam annotation in the controller',
                $name
            ));
        }

        return $this->params[$name];
    }
    
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->params;
    }
}
