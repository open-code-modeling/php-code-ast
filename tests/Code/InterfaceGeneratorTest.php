<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

use OpenCodeModeling\CodeAst\Code\AttributeGenerator;
use OpenCodeModeling\CodeAst\Code\InterfaceGenerator;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class InterfaceGeneratorTest extends TestCase
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
    public function it_generates_interface(): void
    {
        $interface = new InterfaceGenerator('MyInterface');

        $expectedOutput = <<<'EOF'
<?php

interface MyInterface
{
}
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$interface->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_interface_with_attributes(): void
    {
        $interface = new InterfaceGenerator('MyInterface');
        $interface->addAttribute(new AttributeGenerator($this->parser, 'MyAttribute'));
        $interface->addAttribute(new AttributeGenerator($this->parser, '\MyExample\MyAttribute'));
        $interface->addAttribute(new AttributeGenerator($this->parser, 'MyAttribute', 1234));
        $interface->addAttribute(new AttributeGenerator($this->parser, 'MyAttribute', 'value: 1234'));
        $interface->addAttribute(new AttributeGenerator($this->parser, 'MyAttribute', 'MyAttribute::VALUE'));
        $interface->addAttribute(new AttributeGenerator($this->parser, 'MyAttribute', 'array("key" => "value")'));
        $interface->addAttribute(new AttributeGenerator($this->parser, 'MyAttribute', '100 + 200'));

        $expectedOutput = <<<'EOF'
<?php

#[MyAttribute]
#[\MyExample\MyAttribute]
#[MyAttribute(1234)]
#[MyAttribute(value: 1234)]
#[MyAttribute(MyAttribute::VALUE)]
#[MyAttribute(array("key" => "value"))]
#[MyAttribute(100 + 200)]
interface MyInterface
{
}
EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$interface->generate()]));
    }
}
