<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

use Generator;
use OpenCodeModeling\CodeAst\Code\DocBlock\DocBlock;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class PropertyGeneratorTest extends TestCase
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
     * @test
     */
    public function it_generates_property_with_doc_block(): void
    {
        $property = new PropertyGenerator('sourceFolder', 'string', false);
        $property->setDocBlockComment('source folder');

        $expectedOutput = <<<'EOF'
<?php

/**
 * source folder
 *
 * @var string
 */
private $sourceFolder;
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$property->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_property_with_overridden_doc_block(): void
    {
        $property = new PropertyGenerator('sourceFolder', 'string', false);
        $property->setDocBlockComment('source folder');
        $property->overrideDocBlock(new DocBlock('Awesome'));

        $expectedOutput = <<<'EOF'
<?php

/**
 * Awesome
 */
private $sourceFolder;
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$property->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_property_with_array_type_doc_block(): void
    {
        $property = new PropertyGenerator('items', 'array');
        $property->setTypeDocBlockHint('array<string, \stdClass>');

        $expectedOutput = <<<'EOF'
<?php

/**
 * @var array<string, \stdClass>
 */
private array $items;
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$property->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_property_with_long_doc_block(): void
    {
        $docBlockComment = <<<'EOF'
Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's
standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a
type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
remaining essentially unchanged.

It is a long established fact that a reader will be distracted by the readable content of a page when looking at
its layout.
EOF;

        $property = new PropertyGenerator('sourceFolder', 'string');
        $property->setDocBlockComment($docBlockComment);

        $expectedOutput = <<<'EOF'
<?php

/**
 * Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's
 * standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a
 * type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
 * remaining essentially unchanged.
 *
 * It is a long established fact that a reader will be distracted by the readable content of a page when looking at
 * its layout.
 *
 * @var string
 */
private string $sourceFolder;
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$property->generate()]));
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
    public function it_generates_property_with_default_value(string $type, $defaultValue, $expectedDefaultValue): void
    {
        $parameter = new PropertyGenerator('myProperty', $type);
        $parameter->setDefaultValue($defaultValue);

        $expectedOutput = <<<PHP
<?php

private $type \$myProperty = 
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
        $expectedOutput .= ';';

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$parameter->generate()]));
    }
}
