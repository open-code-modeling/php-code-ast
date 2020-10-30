<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Factory;

use OpenCodeModeling\CodeAst\Factory\ClassFactory;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassFactoryTest extends TestCase
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
    public function it_generates_class_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $classFactory = ClassFactory::fromScratch('TestClass', 'My\\Awesome\\Service');
        $classFactory
            ->setFinal(true)
            ->setExtends('BaseClass')
            ->setNamespaceUse('Foo\\Bar')
            ->setImplements('\\Iterator', 'Bar');

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
final class TestClass extends BaseClass implements \Iterator, Bar
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
