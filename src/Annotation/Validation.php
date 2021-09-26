<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *     @Attribute("groups", type="array<string>")
 * })
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Validation
{
    /** @var array<string> */
    private $groups;

    /**
     * @param array{groups: array<string>} $data
     */
    public function __construct(array $data = [], array $value = [])
    {
		if (isset($data['value'])) {
			trigger_deprecation('Condenast\BasicApiBundle', '2.1', 'Passing an array as first argument to "%s" is deprecated. Use named arguments instead.', __METHOD__);
		}

		$data['value'] = $data['value'] ?? $value;

        $this->groups = $data['groups'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
