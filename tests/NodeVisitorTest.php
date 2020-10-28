<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst;

use OpenCodeModeling\CodeAst\NodeVisitor\Collector;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class NodeVisitorTest extends TestCase
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
    public function it_detects_visitors_for_class_and_constant(): void
    {
        $expectedCode = <<<'EOF'
<?php

class TestClass
{
    public const TYPE_STRING = 'string';
}
EOF;

        $ast = $this->parser->parse($expectedCode);

        $visitorCollector = new Collector();

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($visitorCollector);

        $nodeTraverser->traverse($ast);

        $detectedVisitors = $visitorCollector->visitors();

        $this->assertCount(2, $detectedVisitors);

        $this->assertCode($expectedCode, $visitorCollector, $ast);
        $this->assertCode($expectedCode, $visitorCollector, $this->parser->parse(''));
    }

    private function assertCode(string $expectedCode, Collector $visitorCollector, array $ast): void
    {
        $nodeTraverser = new NodeTraverser();

        $visitorCollector->injectVisitors($nodeTraverser);

        $this->assertSame($expectedCode, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
