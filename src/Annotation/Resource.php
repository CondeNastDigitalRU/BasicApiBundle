<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("value", type="string", required=true),
 * })
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Resource
{
    /** @var string */
    private $name;

    /**
     * @param array{value: string} $data
     */
    public function __construct(array $data = [], string $value = '')
    {
		if (isset($data['value'])) {
			trigger_deprecation('Condenast\BasicApiBundle', '2.1', 'Passing an array as first argument to "%s" is deprecated. Use named arguments instead.', __METHOD__);
		}

		$data['value'] = $data['value'] ?? $value;

        if ('' === $data['value']) {
            throw new \InvalidArgumentException('The resource name must be a non-empty string');
        }
        $this->name = $data['value'];
    }

    public function getName(): string
    {
        return $this->name;
    }
}
