<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Controller;

use Condenast\BasicApiBundle\Response\ApiResponse;
use Condenast\BasicApiBundle\Util\Sanitizer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class ErrorController
{
    /** @var bool */
    private $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function __invoke(\Throwable $exception): ApiResponse
    {
        $exception = FlattenException::createFromThrowable($exception);

        return new ApiResponse(
            $this->debug ? Sanitizer::sanitizeRecursive($exception->toArray()) : ['message' => $exception->getStatusText()],
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
    }
}
