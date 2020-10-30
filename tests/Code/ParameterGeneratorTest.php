<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

use Generator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ParameterGeneratorTest extends TestCase
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
     * Values are: type
     *
     * @return Generator
     */
    public function provideTypes(): Generator
    {
        yield 'string' => ['string'];
        yield 'bool' => ['bool'];
        yield 'int' => ['int'];
        yield 'float' => ['float'];
        yield '\\Awesome\\AcmeClass' => ['\\Awesome\\AcmeClass'];
        yield '\\Foo' => ['\\Foo'];

        yield 'nullable string' => ['?string'];
        yield 'nullable bool' => ['?bool'];
        yield 'nullable int' => ['?int'];
        yield 'nullable float' => ['?float'];
        yield 'nullable \\Awesome\\AcmeClass' => ['?\\Awesome\\AcmeClass'];
        yield 'nullable \\Foo' => ['?\\Foo'];
    }

    /**
     * @test
     * @dataProvider provideTypes
     * @param string $type
     */
    public function it_generates_type(string $type): void
    {
        $parameter = new ParameterGenerator('myParameter', $type);

        $expectedOutput = <<<PHP
<?php

$type \$myParameter
PHP;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$parameter->generate()]));
    }
}