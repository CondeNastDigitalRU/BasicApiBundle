<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Unit\Request;

use Condenast\BasicApiBundle\Request\ParamFetcher;
use Condenast\BasicApiBundle\Tests\Unit\ObjectMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;

final class ParamFetcherTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_extract_values(): void
    {
        $path = '[path]';
        $value = 'value';
        $parameters = ['path' => $value];
        $paramFetcher = new ParamFetcher($parameters, ObjectMother::propertyAccessor(), ObjectMother::validator());

        self::assertSame($value, $paramFetcher->get($path));
    }

    /**
     * @test
     */
    public function it_can_extract_nested_values(): void
    {
        $path = '[path][nested]';
        $value = 'value';
        $parameters = ['path' => ['nested' => $value]];

        $paramFetcher = new ParamFetcher($parameters, ObjectMother::propertyAccessor(), ObjectMother::validator());

        self::assertSame($value, $paramFetcher->get($path));
    }

    /**
     * @test
     */
    public function it_returns_a_default_value_if_no_value_is_found(): void
    {
        $paramFetcher = new ParamFetcher([], ObjectMother::propertyAccessor(), ObjectMother::validator());

        self::assertSame('default', $paramFetcher->get('path', [], false, 'default'));
    }

    /**
     * @test
     */
    public function it_returns_a_default_value_if_the_parameter_value_does_not_meet_the_constraints_requirements(): void
    {
        $paramFetcher = new ParamFetcher(['path' => 'value'], ObjectMother::propertyAccessor(), ObjectMother::validator());

        self::assertSame(
            'default',
            $paramFetcher->get('path', [new Length(['min' => 7])], false, 'default')
        );

        self::assertSame(
            [],
            $paramFetcher->get('path', [], true, [])
        );
    }
}
