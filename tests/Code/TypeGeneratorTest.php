<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

use Generator;
use OpenCodeModeling\CodeAst\Code\TypeGenerator;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class TypeGeneratorTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Standard
     */
    private $printer;

    public function setUp(): void
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard(['shortArraySyntax' => true]);
    }

    /**
     * Values are: type, expected output
     *
     * @return Generator
     */
    public function provideTypes(): Generator
    {
        yield 'string' => ['string', 'string'];
        yield 'bool' => ['bool', 'bool'];
        yield 'boolean' => ['bool', 'bool'];
        yield 'int' => ['int', 'int'];
        yield 'integer' => ['int', 'int'];
        yield 'float' => ['float', 'float'];
        yield '\\Awesome\\AcmeClass' => ['\\Awesome\\AcmeClass', '\\Awesome\\AcmeClass'];
        yield '\\Foo' => ['\\Foo', '\\Foo'];
    }

    /**
     * @test
     * @dataProvider provideTypes
     * @param string $type
     * @param string $expectedOutput
     */
    public function it_generates_type(string $type, string $expectedOutput): void
    {
        $type = TypeGenerator::fromTypeString($type);

        $expectedOutput = <<<PHP
<?php

$expectedOutput
PHP;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$type->generate()]));
    }

    /**
     * @test
     * @dataProvider provideTypes
     * @param string $type
     * @param string $expectedOutput
     */
    public function it_generates_nullable_type(string $type, string $expectedOutput): void
    {
        $type = TypeGenerator::fromTypeString('?'. $type);

        $expectedOutput = <<<PHP
<?php

?$expectedOutput
PHP;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$type->generate()]));
    }
}
