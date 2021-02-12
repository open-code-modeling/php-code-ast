<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

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
    private Parser $parser;

    /**
     * @var Standard
     */
    private Standard $printer;

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

    /**
     * Values are: type
     *
     * @return Generator
     */
    public function provideDefaultValues(): Generator
    {
        yield 'string' => ['string', 'abc', 'abc'];
        yield 'string null' => ['string', null, null];
        yield 'array' => ['array', [], []];
        yield 'array null' => ['array', null, null];
        yield 'bool null' => ['string', null, null];
        yield 'bool "true"' => ['bool', 'true', 'true'];
        yield 'bool true' => ['bool', true, true];
        yield 'int null' => ['string', null, null];
        yield 'int' => ['int', 0, 0];
        yield 'float' => ['float', 1.2, 1.2];
        yield 'float null' => ['string', null, null];

        yield 'nullable array' => ['?array', null, null];
        yield 'nullable string' => ['?string', null, null];
        yield 'nullable bool' => ['?bool', null, null];
        yield 'nullable int' => ['?int', null, null];
        yield 'nullable float' => ['?float', null, null];
    }

    /**
     * @test
     * @dataProvider provideDefaultValues
     * @param string $type
     * @param $defaultValue
     * @param $expectedDefaultValue
     */
    public function it_generates_type_with_default_value(string $type, $defaultValue, $expectedDefaultValue): void
    {
        $parameter = new ParameterGenerator('myParameter', $type);
        $parameter->setDefaultValue($defaultValue);

        $expectedOutput = <<<PHP
<?php

$type \$myParameter = 
PHP;

        switch (true) {
            case \is_string($expectedDefaultValue):
                $expectedOutput .= "'" . $expectedDefaultValue . "'";
                break;
            case \is_bool($expectedDefaultValue):
                $expectedOutput .= 'true';
                break;
            case \is_null($expectedDefaultValue):
            case $expectedDefaultValue === 'null':
                $expectedOutput .= 'null';
                break;
            case $expectedDefaultValue === []:
                $expectedOutput .= '[]';
                break;
            default:
                $expectedOutput .= $expectedDefaultValue;
                break;
        }

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$parameter->generate()]));
    }

    /**
     * @test
     */
    public function it_works_without_type(): void
    {
        $parameter = new ParameterGenerator('myParameter');

        $expectedOutput = <<<PHP
<?php

\$myParameter
PHP;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$parameter->generate()]));
    }
}
