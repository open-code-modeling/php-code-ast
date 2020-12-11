<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\Exception\LogicException;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassExtends;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassExtendsTest extends TestCase
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
    public function it_generates_class_extends_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassExtends('MyBaseClass'));

        $expected = <<<EOF
<?php

class TestClass extends MyBaseClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_checks_class_extends_for_existing_file(): void
    {
        $ast = $this->parser->parse('<?php class TestClass {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassExtends('MyBaseClass'));

        $expected = <<<EOF
<?php

class TestClass extends MyBaseClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_class_extends_with_namespace_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassExtends('MyBaseClass'));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass extends MyBaseClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_class_extends_for_namespace_file(): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassExtends('MyBaseClass'));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass extends MyBaseClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_checks_class_extends_with_namespace_for_existing_file(): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass {}
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassExtends('MyBaseClass'));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass extends MyBaseClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_throws_exception_if_multi_inheritance(): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass extends OtherBaseClass {}
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassExtends('MyBaseClass'));

        $this->expectException(LogicException::class);

        $nodeTraverser->traverse($ast);
    }
}
