<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\InterfaceGenerator;
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\InterfaceFile;
use OpenCodeModeling\CodeAst\NodeVisitor\InterfaceMethod;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class InterfaceMethodTest extends TestCase
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
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));
        $nodeTraverser->addVisitor(new InterfaceMethod($this->method));

        $expected = <<<'EOF'
<?php

interface TestInterface
{
    public function testMethod(string $arg) : string;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_method_for_class_for_existing_file(): void
    {
        $ast = $this->parser->parse('<?php interface TestInterface {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceMethod($this->method));

        $expected = <<<'EOF'
<?php

interface TestInterface
{
    public function testMethod(string $arg) : string;
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
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));
        $nodeTraverser->addVisitor(new InterfaceMethod($this->method));

        $expected = <<<'EOF'
<?php

namespace My\Awesome\Service;

interface TestInterface
{
    public function testMethod(string $arg) : string;
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

interface TestInterface {}
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceMethod($this->method));

        $expected = <<<'EOF'
<?php

namespace My\Awesome\Service;

interface TestInterface
{
    public function testMethod(string $arg) : string;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
