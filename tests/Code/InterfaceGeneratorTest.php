<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

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
}
