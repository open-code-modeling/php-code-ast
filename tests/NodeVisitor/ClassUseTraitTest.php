<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\NodeVisitor\ClassUseTrait;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassUseTraitTest extends TestCase
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
    public function it_add_namespace_imports_in_correct_order(): void
    {
        $ast = $this->parser->parse($this->classCode());

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassUseTrait('\MyService\Foo', 'MyService\Bar', 'MyService\FooBar'));

        $this->assertSame($this->expectedClassCode(), $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    private function classCode(): string
    {
        return <<<'EOF'
        <?php

        declare(strict_types=1);

        namespace My\Awesome\Service;

        class TestClass
        {
            use \MyService\Foo;
            use MyService\FooBar;

            public function testMethod()
            {
            }
        }
        EOF;
    }

    private function expectedClassCode(): string
    {
        return <<<'EOF'
        <?php

        declare (strict_types=1);
        namespace My\Awesome\Service;

        class TestClass
        {
            use MyService\Bar;
            use \MyService\Foo;
            use MyService\FooBar;
            public function testMethod()
            {
            }
        }
        EOF;
    }
}
