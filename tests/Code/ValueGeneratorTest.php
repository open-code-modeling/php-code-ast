<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

use Generator;
use OpenCodeModeling\CodeAst\Code\ValueGenerator;
use PhpParser\Node;
use PHPUnit\Framework\TestCase;

final class ValueGeneratorTest extends TestCase
{
    /**
     * Values are: type, expected output
     *
     * @return Generator
     */
    public function provideTypes(): Generator
    {
        yield 'null' => [null, Node\Expr\ConstFetch::class];
        yield 'string' => ['test string', Node\Scalar\String_::class];
        yield 'bool' => [true, Node\Expr\ConstFetch::class];
        yield 'int' => [1, Node\Scalar\LNumber::class];
        yield 'integer' => [10, Node\Scalar\LNumber::class];
        yield 'float' => [2.523, Node\Scalar\DNumber::class];
        yield 'double' => [7E-10, Node\Scalar\DNumber::class];
        yield 'array' => [['one', 'two'], Node\Expr\Array_::class];
        yield 'other node expression' => [new Node\Expr\Array_(), Node\Expr\Array_::class];
    }

    /**
     * @test
     * @dataProvider provideTypes
     * @param mixed $value
     * @param string $expectedGeneratedValue
     */
    public function it_supports_type($value, string $expectedGeneratedValue): void
    {
        $value = new ValueGenerator($value);

        $this->assertInstanceOf($expectedGeneratedValue, $value->generate());
    }
}
