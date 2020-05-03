<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Response;

use Symfony\Component\HttpFoundation\Response;

class ApiResponse extends Response
{
    /** @var mixed */
    private $data;

    /** @var bool */
    private $statusCodeSet;

    /**
     * @param mixed $data
     * @param array<string, string|array> $headers
     */
    public function __construct($data = null, ?int $status = null, array $headers = [])
    {
        parent::__construct('', $status ?? self::HTTP_OK, $headers);

        $this->data = $data;
        $this->statusCodeSet = null !== $status;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function isStatusCodeSet(): bool
    {
        return $this->statusCodeSet;
    }

    /**
     * @param string|false|null $text
     */
    public function setStatusCode(int $code, $text = null): object
    {
        $this->statusCodeSet = true;
        return parent::setStatusCode($code, $text);
    }
}
