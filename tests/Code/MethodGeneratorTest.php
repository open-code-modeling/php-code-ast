<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class MethodGeneratorTest extends TestCase
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
     * @test
     */
    public function it_generates_method_with_doc_block(): void
    {
        $method = new MethodGenerator(
            'setType',
            [
                new ParameterGenerator('type', '?string'),
            ]
        );
        $method->setDocBlockComment('Sets an awesome type');

        $expectedOutput = <<<'EOF'
<?php

/**
 * Sets an awesome type
 *
 * @var string|null $type
 */
public function setType(?string $type);
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_array_type_doc_block(): void
    {
        $parameter = new ParameterGenerator('items', 'array');
        $parameter->setTypeDocBlockHint('array<string, \stdClass>');

        $method = new MethodGenerator(
            'setItems',
            [
                $parameter,
            ]
        );
        $method->setDocBlockComment('Sets awesome items');

        $expectedOutput = <<<'EOF'
<?php

/**
 * Sets awesome items
 *
 * @var array<string, \stdClass> $items
 */
public function setItems(array $items);
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_long_doc_block(): void
    {
        $docBlockComment = <<<'EOF'
Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's
standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a
type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
remaining essentially unchanged.

It is a long established fact that a reader will be distracted by the readable content of a page when looking at
its layout.
EOF;


        $method = new MethodGenerator(
            'setType',
            [
                new ParameterGenerator('type', 'string'),
                new ParameterGenerator('value', '?int'),
            ]
        );
        $method->setDocBlockComment($docBlockComment);

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
 * @var string $type
 * @var int|null $value
 */
public function setType(string $type, ?int $value);
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }
}
