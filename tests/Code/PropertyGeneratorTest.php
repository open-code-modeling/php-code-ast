<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

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
        $property = new PropertyGenerator('sourceFolder', 'string', null, false);
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
        $property = new PropertyGenerator('sourceFolder', 'string', null, false);
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
}
