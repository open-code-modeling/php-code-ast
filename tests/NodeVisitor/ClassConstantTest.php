<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassConstant;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassUseTrait;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassConstantTest extends TestCase
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
    public function it_generates_constant_for_class_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new StrictType());
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(ClassConstant::forClassConstant('TYPE_STRING', 'string'));

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
class TestClass
{
    public const TYPE_STRING = 'string';
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_constant_for_class_for_existing_file(): void
    {
        $ast = $this->parser->parse('<?php class TestClass {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(ClassConstant::forClassConstant('TYPE_STRING', 'string', ClassConstGenerator::FLAG_PRIVATE));

        $expected = <<<'EOF'
<?php

class TestClass
{
    private const TYPE_STRING = 'string';
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_constant_for_class_with_namespace_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new StrictType());
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(ClassConstant::forClassConstant('TYPE_STRING', 'string'));

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public const TYPE_STRING = 'string';
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_constant_for_class_with_namespace_for_existing_file(): void
    {
        $code = <<<'PHP'
<?php

namespace My\Awesome\Service;

class TestClass {}
PHP;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(ClassConstant::forClassConstant('TYPE_STRING', 'string', ClassConstGenerator::FLAG_PRIVATE));

        $expected = <<<'EOF'
<?php

namespace My\Awesome\Service;

class TestClass
{
    private const TYPE_STRING = 'string';
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_preserves_order_of_registered_visitors()
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new StrictType());
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new NamespaceUse('My\\Awesome\\ServiceTrait'));
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassUseTrait('ServiceTrait'));
        $nodeTraverser->addVisitor(ClassConstant::forClassConstant('TYPE_STRING', 'string'));
        $nodeTraverser->addVisitor(ClassConstant::forClassConstant('TYPE_INT', 3));

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

use My\Awesome\ServiceTrait;
class TestClass
{
    use ServiceTrait;
    public const TYPE_STRING = 'string';
    public const TYPE_INT = 3;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
