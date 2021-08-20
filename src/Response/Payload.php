<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Response;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Payload
{
    /** @var iterable|object|null */
    private $data;

    /** @var int */
    private $status;

    /** @var array */
    private $serializationContext;

    /** @var ResponseHeaderBag */
    private $headers;

    /**
     * @param iterable|object|null $data
     */
    public function __construct($data, int $status = 200, array $serializationContext = [], array $headers = [])
    {
        $this->setData($data);
        $this->status = $status;
        $this->serializationContext = $serializationContext;
        $this->headers = new ResponseHeaderBag($headers);
    }

    /**
     * @return iterable|object|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param iterable|object|null $data
     */
    public function setData($data): void
    {
        if (!\in_array(\gettype($data), ['array', 'object', 'NULL'])) {
            throw new \RuntimeException('Data must be one of the following types: array, object, NULL');
        }

        $this->data = $data;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getSerializationContext(): array
    {
        return $this->serializationContext;
    }

    public function setSerializationContext(array $serializationContext): void
    {
        $this->serializationContext = $serializationContext;
    }

    /**
     * @param string|array<string> $value
     */
    public function setHeader(string $name, $value, bool $replace = true): void
    {
        $this->headers->set($name, $value, $replace);
    }

    public function setHeaders(array $headers): void
    {
        $this->headers->replace($headers);
    }

    public function getHeaders(): array
    {
        return $this->headers->all();
    }
}
