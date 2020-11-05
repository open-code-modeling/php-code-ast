<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\InterfaceBuilder;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class InterfaceBuilderTest extends TestCase
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
    public function it_generates_interface_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $interfaceFactory = InterfaceBuilder::fromScratch('TestInterface', 'My\\Awesome\\Service');
        $interfaceFactory
            ->setExtends('BaseClass')
            ->setNamespaceUse('Foo\\Bar')
            ->setConstants(ClassConstBuilder::fromScratch('PRIV', 'private')->setPrivate());

        $nodeTraverser = new NodeTraverser();
        $interfaceFactory->injectVisitors($nodeTraverser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
interface TestInterface extends BaseClass
{
    private const PRIV = 'private';
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_interface_for_empty_file_from_template(): void
    {
        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
interface TestInterface extends BaseClass
{
    const FIRST = 1;
    private const PRIV = 'private';
    protected const PROT = 'protected';
    public const PUB = 'public';
}
EOF;

        $ast = $this->parser->parse($expected);

        $interfaceFactory = InterfaceBuilder::fromNodes(...$ast);

        $this->assertSame('TestInterface', $interfaceFactory->getName());
        $this->assertSame(['BaseClass'], $interfaceFactory->getExtends());
        $this->assertTrue($interfaceFactory->isStrict());

        $nodeTraverser = new NodeTraverser();
        $interfaceFactory->injectVisitors($nodeTraverser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }
}
