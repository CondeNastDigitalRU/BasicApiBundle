<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Exception;

use Throwable;

class InvalidArgumentException extends \InvalidArgumentException
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
