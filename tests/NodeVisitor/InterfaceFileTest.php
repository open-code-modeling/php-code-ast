<?php

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\InterfaceGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\InterfaceFile;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class InterfaceFileTest extends TestCase
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
    public function it_generates_interface_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));

        $expected = <<<EOF
<?php

interface TestInterface
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_checks_interface_for_existing_file(): void
    {
        $ast = $this->parser->parse('<?php interface TestInterface {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));

        $expected = <<<EOF
<?php

interface TestInterface
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_interface_with_namespace_for_empty_file(): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

interface TestInterface
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_interface_for_namespace_file(): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

interface TestInterface
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_checks_interface_with_namespace_for_existing_file(): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;

interface TestInterface {}
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

interface TestInterface
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

}
