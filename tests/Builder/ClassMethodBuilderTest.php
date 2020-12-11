<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\ParameterBuilder;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassMethodBuilderTest extends TestCase
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
    public function it_generates_method_for_empty_class(): void
    {
        $ast = $this->parser->parse('');

        $classFactory = ClassBuilder::fromScratch('TestClass', 'My\\Awesome\\Service');
        $classFactory->setMethods(
            ClassMethodBuilder::fromScratch('setActive')->setReturnType('void')->setStatic(true)
        );
        $classFactory->addMethod(
            ClassMethodBuilder::fromScratch('doSomething')->setReturnType('void')->setFinal(true)
        );

        $this->assertTrue($classFactory->hasMethod('setActive'));
        $this->assertTrue($classFactory->hasMethod('doSomething'));

        $methods = $classFactory->getMethods();

        $this->assertCount(2, $methods);

        $this->assertSame('setActive', $methods['setActive']->getName());
        $this->assertFalse($methods['setActive']->isAbstract());
        $this->assertFalse($methods['setActive']->isFinal());
        $this->assertTrue($methods['setActive']->isStatic());

        $this->assertSame('doSomething', $methods['doSomething']->getName());
        $this->assertFalse($methods['doSomething']->isAbstract());
        $this->assertTrue($methods['doSomething']->isFinal());

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
    }
    public final function doSomething() : void
    {
    }
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_abstract_method_for_empty_class(): void
    {
        $ast = $this->parser->parse('');

        $classFactory = ClassBuilder::fromScratch('TestClass', 'My\\Awesome\\Service');
        $classFactory->setMethods(ClassMethodBuilder::fromScratch('setActive')->setReturnType('void')->setAbstract(true));

        $methods = $classFactory->getMethods();

        $this->assertCount(1, $methods);

        $this->assertSame('setActive', $methods['setActive']->getName());
        $this->assertTrue($methods['setActive']->isAbstract());
        $this->assertFalse($methods['setActive']->isFinal());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public abstract function setActive() : void;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_method_for_empty_class_from_template(): void
    {
        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public function setActive() : void
    {
    }
    protected final function doSomething() : ?MyResult
    {
    }
}
EOF;

        $ast = $this->parser->parse($expected);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $methods = \array_values($classFactory->getMethods());

        $this->assertCount(2, $methods);

        $this->assertSame('setActive', $methods[0]->getName());
        $this->assertFalse($methods[0]->isAbstract());
        $this->assertFalse($methods[0]->isFinal());
        $this->assertTrue($methods[0]->isPublic());
        $this->assertSame('void', $methods[0]->getReturnType());

        $this->assertSame('doSomething', $methods[1]->getName());
        $this->assertFalse($methods[1]->isAbstract());
        $this->assertTrue($methods[1]->isFinal());
        $this->assertTrue($methods[1]->isProtected());
        $this->assertSame('?MyResult', $methods[1]->getReturnType());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_generates_abstract_method_for_empty_class_from_template(): void
    {
        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public abstract function setActive() : void;
}
EOF;

        $ast = $this->parser->parse($expected);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $methods = \array_values($classFactory->getMethods());

        $this->assertCount(1, $methods);

        $this->assertSame('setActive', $methods[0]->getName());
        $this->assertTrue($methods[0]->isAbstract());
        $this->assertFalse($methods[0]->isFinal());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_generates_method_with_args_for_empty_class(): void
    {
        $ast = $this->parser->parse('');

        $methodBuilder = ClassMethodBuilder::fromScratch('setActive')->setReturnType('void');
        $methodBuilder->setParameters(ParameterBuilder::fromScratch('active', 'bool'));

        $classFactory = ClassBuilder::fromScratch('TestClass', 'My\\Awesome\\Service');
        $classFactory->setMethods($methodBuilder);

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public function setActive(bool $active) : void
    {
    }
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_method_with_args_for_empty_class_from_template(): void
    {
        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public function setActive(?bool $active) : void
    {
    }
}
EOF;

        $ast = $this->parser->parse($expected);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $methods = \array_values($classFactory->getMethods());

        $this->assertCount(1, $methods);
        $this->assertCount(1, $methods[0]->getParameters());

        $this->assertSame('setActive', $methods[0]->getName());
        $this->assertTrue($methods[0]->isPublic());

        $parameters = $methods[0]->getParameters();

        $this->assertCount(1, $parameters);

        $parameter = $parameters[0];

        $this->assertSame('active', $parameter->getName());
        $this->assertSame('?bool', $parameter->getType());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_sort_of_class_methods(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public function b() : void
    {
    }
    public function a() : void
    {
    }
    public function d() : void
    {
    }
    public function c() : void
    {
    }
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $classFactory->sortMethods(function (ClassMethodBuilder $a, ClassMethodBuilder $b) {
            return $a->getName() <=> $b->getName();
        });

        $methods = \array_values($classFactory->getMethods());
        $this->assertCount(4, $methods);
        $this->assertSame('a', $methods[0]->getName());
        $this->assertSame('b', $methods[1]->getName());
        $this->assertSame('c', $methods[2]->getName());
        $this->assertSame('d', $methods[3]->getName());

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public function a() : void
    {
    }
    public function b() : void
    {
    }
    public function c() : void
    {
    }
    public function d() : void
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
    public function it_removes_method(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public function setActive() : void
    {
    }
    public final function doSomething() : void
    {
    }
}
EOF;
        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public function setActive() : void
    {
    }
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $methods = $classFactory->getMethods();

        $this->assertCount(2, $methods);

        $this->assertSame('doSomething', $methods['doSomething']->getName());
        $classFactory->removeMethod($methods['doSomething']->getName());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }
}
