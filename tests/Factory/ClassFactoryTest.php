<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Factory;

use OpenCodeModeling\CodeAst\Factory\ClassConstFactory;
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
            ->setImplements('\\Iterator', 'Bar')
            ->setConstants(ClassConstFactory::fromScratch('PRIV', 'private')->setPrivate());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
final class TestClass extends BaseClass implements \Iterator, Bar
{
    private const PRIV = 'private';
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_class_for_empty_file_from_template(): void
    {
        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
final class TestClass extends BaseClass implements \Iterator, Bar
{
    const FIRST = 1;
    private const PRIV = 'private';
    protected const PROT = 'protected';
    public const PUB = 'public';
}
EOF;

        $ast = $this->parser->parse($expected);

        $classFactory = ClassFactory::fromNodes(...$ast);

        $this->assertSame('TestClass', $classFactory->getName());
        $this->assertSame('BaseClass', $classFactory->getExtends());
        $this->assertTrue($classFactory->isFinal());
        $this->assertTrue($classFactory->isStrict());
        $this->assertFalse($classFactory->isAbstract());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }
}
