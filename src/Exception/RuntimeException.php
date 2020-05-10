<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Exception;

use Throwable;

class RuntimeException extends \RuntimeException
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
