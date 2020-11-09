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
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassBuilderTest extends TestCase
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

        $classFactory = ClassBuilder::fromScratch('TestClass', 'My\\Awesome\\Service');
        $classFactory
            ->setFinal(true)
            ->setExtends('BaseClass')
            ->setNamespaceUse('Foo\\Bar')
            ->setImplements('\\Iterator', 'Bar')
            ->setUseTrait('\\My\\TestTrait')
            ->setConstants(ClassConstBuilder::fromScratch('PRIV', 'private')->setPrivate());

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
        $this->assertSame(['\\My\\TestTrait'], $classFactory->getUseTrait());

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
        $this->assertSame('FIRST', $constants[0]->getName());
        $this->assertSame('PRIV', $constants[1]->getName());
        $this->assertSame('PROT', $constants[2]->getName());
        $this->assertSame('PUB', $constants[3]->getName());

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

        $classFactory->sortNamespaceUse(function (string $a, string $b) {
            return $a <=> $b;
        });

        $namespaceUse = $classFactory->getNamespaceUse();
        $this->assertCount(4, $namespaceUse);
        $this->assertSame('My\\A', $namespaceUse[0]);
        $this->assertSame('My\\B', $namespaceUse[1]);
        $this->assertSame('My\\C', $namespaceUse[2]);
        $this->assertSame('My\\D', $namespaceUse[3]);

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

        $useTrait = $classFactory->getUseTrait();
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
}
