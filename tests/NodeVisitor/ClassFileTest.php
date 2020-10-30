<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassFileTest extends TestCase
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

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));

        $expected = <<<EOF
<?php

class TestClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_checks_class_for_existing_file(): void
    {
        $ast = $this->parser->parse('<?php class TestClass {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));

        $expected = <<<EOF
<?php

class TestClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_class_with_namespace_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_class_for_namespace_file(): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_checks_class_with_namespace_for_existing_file(): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass {}
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

}
