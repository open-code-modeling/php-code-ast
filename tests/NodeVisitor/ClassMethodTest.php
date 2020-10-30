<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassMethodTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Standard
     */
    private $printer;

    /**
     * @var MethodGenerator
     */
    private $method;

    public function setUp(): void
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard(['shortArraySyntax' => true]);
        $this->method = new MethodGenerator(
            'testMethod',
            [
                new ParameterGenerator('arg', 'string'),
            ],
            MethodGenerator::FLAG_PUBLIC,
            new BodyGenerator($this->parser, "return 'test';") // will be removed by visitor
        );
        $this->method->setReturnType('string');
        $this->method->setTyped(true);
    }

    /**
     * @test
     */
    public function it_generates_method_for_class_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassMethod($this->method));

        $expected = <<<'EOF'
<?php

class TestClass
{
    public function testMethod(string $arg) : string
    {
        return 'test';
    }
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_method_for_class_for_existing_file(): void
    {
        $ast = $this->parser->parse('<?php class TestClass {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassMethod($this->method));

        $expected = <<<'EOF'
<?php

class TestClass
{
    public function testMethod(string $arg) : string
    {
        return 'test';
    }
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_method_for_class_with_namespace_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassMethod($this->method));

        $expected = <<<'EOF'
<?php

namespace My\Awesome\Service;

class TestClass
{
    public function testMethod(string $arg) : string
    {
        return 'test';
    }
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_method_for_class_with_namespace_for_existing_file(): void
    {
        $code = <<<'EOF'
<?php

namespace My\Awesome\Service;

class TestClass {}
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassMethod($this->method));

        $expected = <<<'EOF'
<?php

namespace My\Awesome\Service;

class TestClass
{
    public function testMethod(string $arg) : string
    {
        return 'test';
    }
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

}
