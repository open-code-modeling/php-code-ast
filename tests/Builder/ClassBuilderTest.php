<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassBuilderTest extends TestCase
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
    public function it_generates_class_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $classFactory = ClassBuilder::fromScratch('TestClass', 'My\\Awesome\\Service');
        $classFactory
            ->setFinal(true)
            ->setExtends('BaseClass')
            ->setNamespaceImports('Foo\\Bar')
            ->setImplements('\\Iterator', 'Bar')
            ->setTraits('\\My\\TestTrait')
            ->setConstants(ClassConstBuilder::fromScratch('PRIV', 'private')->setPrivate());

        $this->assertTrue($classFactory->hasConstant('PRIV'));
        $this->assertTrue($classFactory->hasImplement('\\Iterator'));
        $this->assertTrue($classFactory->hasImplement('Bar'));
        $this->assertTrue($classFactory->hasTrait('\\My\\TestTrait'));
        $this->assertTrue($classFactory->hasNamespaceImport('Foo\\Bar'));

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
final class TestClass extends BaseClass implements \Iterator, Bar
{
    use \My\TestTrait;
    private const PRIV = 'private';
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse($expected))));
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
    use \My\TestTrait;
    const FIRST = 1;
    private const PRIV = 'private';
    protected const PROT = 'protected';
    public const PUB = 'public';
}
EOF;

        $ast = $this->parser->parse($expected);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $this->assertSame('TestClass', $classFactory->getName());
        $this->assertSame('BaseClass', $classFactory->getExtends());
        $this->assertTrue($classFactory->isFinal());
        $this->assertTrue($classFactory->isStrict());
        $this->assertFalse($classFactory->isAbstract());
        $this->assertSame(['\\My\\TestTrait' => '\\My\\TestTrait'], $classFactory->getTraits());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_sort_of_class_constants(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    protected const PROT = 'protected';
    private const PRIV = 'private';
    public const PUB = 'public';
    const FIRST = 1;
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $classFactory->sortConstants(function (ClassConstBuilder $a, ClassConstBuilder $b) {
            return $a->getName() <=> $b->getName();
        });

        $constants = $classFactory->getConstants();
        $this->assertCount(4, $constants);
        $this->assertSame('FIRST', $constants['FIRST']->getName());
        $this->assertSame('PRIV', $constants['PRIV']->getName());
        $this->assertSame('PROT', $constants['PROT']->getName());
        $this->assertSame('PUB', $constants['PUB']->getName());

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    const FIRST = 1;
    private const PRIV = 'private';
    protected const PROT = 'protected';
    public const PUB = 'public';
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_adding_of_class_constants_before_property(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    use TestTrait;
    protected $property;
    
    public function test(): void
    {
    }
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);
        $classFactory->addConstant(ClassConstBuilder::fromScratch('FIRST', 1));
        $classFactory->addConstant(ClassConstBuilder::fromScratch('PUB', 'public'));

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    use TestTrait;
    public const FIRST = 1;
    public const PUB = 'public';
    protected $property;
    public function test() : void
    {
    }
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_adding_of_class_constants_before_methods_with_doc_blocks(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    /**
     * Define aggregate names using constants
     *
     * @example
     *
     * const USER = 'User';
     */


    /**
     * @param EventEngine $eventEngine
     */
    public static function describe(EventEngine $eventEngine): void
    {
    }
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromScratch('TestClass', 'My\Awesome\Service');
        $classFactory->addConstant(ClassConstBuilder::fromScratch('FIRST', 1));
        $classFactory->addConstant(ClassConstBuilder::fromScratch('PUB', 'public'));

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    public const FIRST = 1;
    public const PUB = 'public';
    /**
     * Define aggregate names using constants
     *
     * @example
     *
     * const USER = 'User';
     */
    /**
     * @param EventEngine $eventEngine
     */
    public static function describe(EventEngine $eventEngine) : void
    {
    }
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_supports_adding_of_class_constants(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    protected const PROT = 'protected';
    
    public function test(): void
    {
    }
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);
        $classFactory->addConstant(ClassConstBuilder::fromScratch('FIRST', 1));
        $classFactory->addConstant(ClassConstBuilder::fromScratch('PUB', 'public'));

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    protected const PROT = 'protected';
    public const FIRST = 1;
    public const PUB = 'public';
    public function test() : void
    {
    }
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_sort_of_namespace_use(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use My\D;
use My\A;
use My\C;
use My\B;

final class TestClass
{
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $classFactory->sortNamespaceImports(function (string $a, string $b) {
            return $a <=> $b;
        });

        $namespaceUse = $classFactory->getNamespaceImports();
        $this->assertCount(4, $namespaceUse);
        $this->assertSame('My\\A', $namespaceUse['My\\A']);
        $this->assertSame('My\\B', $namespaceUse['My\\B']);
        $this->assertSame('My\\C', $namespaceUse['My\\C']);
        $this->assertSame('My\\D', $namespaceUse['My\\D']);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use My\A;
use My\B;
use My\C;
use My\D;
final class TestClass
{
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_adding_namespace_imports(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use My\C;
use My\B;

final class TestClass
{
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $classFactory->addNamespaceImport('My\A', 'My\C', 'My\B', 'My\D');

        $namespaceUse = \array_values($classFactory->getNamespaceImports());
        $this->assertCount(4, $namespaceUse);
        $this->assertSame('My\\C', $namespaceUse[0]);
        $this->assertSame('My\\B', $namespaceUse[1]);
        $this->assertSame('My\\A', $namespaceUse[2]);
        $this->assertSame('My\\D', $namespaceUse[3]);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use My\A;
use My\D;
use My\C;
use My\B;
final class TestClass
{
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse($code))));
    }

    /**
     * @test
     */
    public function it_supports_removing_namespace_imports(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use My\C;
use My\B;

final class TestClass
{
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);
        $classFactory->removeNamespaceImport('My\A', 'My\C');

        $namespaceUse = $classFactory->getNamespaceImports();
        $this->assertCount(1, $namespaceUse);
        $this->assertSame('My\\B', $namespaceUse['My\\B']);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use My\B;
final class TestClass
{
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_sort_of_traits(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    use My\D;
    use My\A;
    use My\C;
    use My\B;
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $classFactory->sortTraits(function (string $a, string $b) {
            return $a <=> $b;
        });

        $useTrait = \array_values($classFactory->getTraits());
        $this->assertCount(4, $useTrait);
        $this->assertSame('My\\A', $useTrait[0]);
        $this->assertSame('My\\B', $useTrait[1]);
        $this->assertSame('My\\C', $useTrait[2]);
        $this->assertSame('My\\D', $useTrait[3]);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    use My\A;
    use My\B;
    use My\C;
    use My\D;
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_adding_of_traits(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    use My\D;
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);
        $classFactory->addTrait('My\\A');

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

final class TestClass
{
    use My\D;
    use My\A;
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_adding_of_node_visitors(): void
    {
        $ast = $this->parser->parse('');

        $classFactory = ClassBuilder::fromScratch('TestClass', 'My\\Awesome\\Service');
        $classFactory->setMethods(
            ClassMethodBuilder::fromScratch('setActive')->setReturnType('void')->setStatic(true)
        );

        $classFactory->addNodeVisitor($this->getSetActiveNodeVisitor());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public static function setActive() : void
    {
        $tmp = $this->get();
    }
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    private function getSetActiveNodeVisitor(): NodeVisitor
    {
        $nodes = $this->parser->parse('<?php $tmp = $this->get();');

        return new class($nodes) extends NodeVisitorAbstract {
            private $nodes;

            public function __construct($nodes)
            {
                $this->nodes = $nodes;
            }

            public function afterTraverse(array $nodes)
            {
                $newNodes = [];

                foreach ($nodes as $node) {
                    $newNodes[] = $node;

                    if (! $node instanceof Node\Stmt\Class_) {
                        continue;
                    }

                    if ($node->stmts[0] instanceof Node\Stmt\ClassMethod
                        && $node->stmts[0]->name instanceof Node\Identifier
                        && $node->stmts[0]->name->name === 'setActive'
                    ) {
                        $node->stmts[0]->stmts = $this->nodes;
                    }
                }

                return $newNodes;
            }
        };
    }
}
