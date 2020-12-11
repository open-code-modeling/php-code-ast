<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
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
    public function it_generates_interface_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $interfaceFactory = InterfaceBuilder::fromScratch('TestInterface', 'My\\Awesome\\Service');
        $interfaceFactory
            ->setExtends('BaseClass')
            ->setNamespaceImports('Foo\\Bar')
            ->setConstants(ClassConstBuilder::fromScratch('PRIV', 'private')->setPrivate())
            ->setMethods(ClassMethodBuilder::fromScratch('getValue')->setReturnType('string'));

        $nodeTraverser = new NodeTraverser();
        $interfaceFactory->injectVisitors($nodeTraverser, $this->parser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use Foo\Bar;
interface TestInterface extends BaseClass
{
    private const PRIV = 'private';
    public function getValue() : string;
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
    public function getValue() : string;
}
EOF;

        $ast = $this->parser->parse($expected);

        $interfaceFactory = InterfaceBuilder::fromNodes(...$ast);

        $this->assertSame('TestInterface', $interfaceFactory->getName());
        $this->assertSame(['BaseClass' => 'BaseClass'], $interfaceFactory->getExtends());
        $this->assertTrue($interfaceFactory->isStrict());
        $this->assertTrue($interfaceFactory->hasMethod('getValue'));
        $this->assertTrue($interfaceFactory->hasConstant('PUB'));

        $nodeTraverser = new NodeTraverser();
        $interfaceFactory->injectVisitors($nodeTraverser, $this->parser);

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

interface TestClass
{
}
EOF;

        $ast = $this->parser->parse($code);

        $interfaceBuilder = InterfaceBuilder::fromNodes(...$ast);

        $interfaceBuilder->addNamespaceImport('My\A', 'My\C', 'My\B', 'My\D');

        $namespaceUse = \array_values($interfaceBuilder->getNamespaceImports());
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
interface TestClass
{
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $interfaceBuilder->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse($code))));
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

interface TestClass
{
}
EOF;

        $ast = $this->parser->parse($code);

        $interfaceBuilder = InterfaceBuilder::fromNodes(...$ast);

        $interfaceBuilder->sortNamespaceImports(function (string $a, string $b) {
            return $a <=> $b;
        });

        $namespaceUse = $interfaceBuilder->getNamespaceImports();
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
interface TestClass
{
}
EOF;
        $nodeTraverser = new NodeTraverser();
        $interfaceBuilder->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }
}
