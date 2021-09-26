<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("argument", type="string", required=true),
 *     @Attribute("type", type="string", required=true),
 *     @Attribute("context", type="array"),
 *     @Attribute("requestAttributes", type="array"),
 * })
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Deserialization
{
    /** @var string */
    private $argument;

    /** @var string */
    private $type;

    /** @var array */
    private $context;

    /** @var array<string, string> [attributeName => propertyPath] */
    private $requestAttributes;

    /**
     * @param array{argument: string, type: string, context: array|null, requestAttributes: array<string, string>|null} $data
     */
	public function __construct(array $data = [], string $argument = '', string $type = '', array $context = [], array $requestAttributes = [])
    {
		$deprecation = false;
		foreach ($data as $key => $val) {
			if (\in_array($key, ['argument', 'type', 'context', 'requestAttributes'])) {
				$deprecation = true;
			}
		}

		if ($deprecation) {
			trigger_deprecation('Condenast\BasicApiBundle', '2.1', 'Passing an array as first argument to "%s" is deprecated. Use named arguments instead.', __METHOD__);
		}

		$data['argument'] = $data['argument'] ?? $argument;
		$data['type'] = $data['type'] ?? $type;
		$data['context'] = $data['context'] ?? $context;
		$data['requestAttributes'] = $data['requestAttributes'] ?? $requestAttributes;

        if ('' === $data['argument']) {
            throw new \InvalidArgumentException('The "argument" attribute must be a non-empty string');
        }

        if ('' === $data['type']) {
            throw new \InvalidArgumentException('The "type" attribute must be a non-empty string');
        }

		$this->argument = $data['argument'];
        $this->type = $data['type'];
        $this->context = $data['context'] ?? [];
        $this->requestAttributes = $data['requestAttributes'] ?? [];
    }

    public function getArgument(): string
    {
        return $this->argument;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, string>
     */
    public function getRequestAttributes(): array
    {
        return $this->requestAttributes;
    }
}
