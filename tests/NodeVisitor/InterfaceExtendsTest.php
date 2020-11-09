<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use Generator;
use OpenCodeModeling\CodeAst\Code\InterfaceGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\InterfaceExtends;
use OpenCodeModeling\CodeAst\NodeVisitor\InterfaceFile;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class InterfaceExtendsTest extends TestCase
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
     * Values are: interfaces
     *
     * @return Generator
     */
    public function provideInterfaces(): Generator
    {
        yield '\\Awesome\\AcmeClass' => [['\\Awesome\\AcmeClass']];
        yield '\\Foo' => [['\\Foo']];

        yield '\\Awesome\\AcmeClass, \\My\\OtherInterface' => [['\\Awesome\\AcmeClass', '\\My\\OtherInterface']];
        yield '\\Foo, \\Bar' => [['\\Foo', '\\Bar']];

        yield 'FirstInterface, SecondInterface, ThirdInterface' => [['FirstInterface', 'SecondInterface', 'ThirdInterface']];
    }

    /**
     * @test
     * @dataProvider provideInterfaces
     * @param array $interfaces
     */
    public function it_generates_class_extends_for_empty_file(array $interfaces): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));
        $nodeTraverser->addVisitor(new InterfaceExtends(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
<?php

interface TestInterface extends $extends
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideInterfaces
     * @param array $interfaces
     */
    public function it_checks_class_extends_for_existing_file(array $interfaces): void
    {
        $ast = $this->parser->parse('<?php interface TestInterface {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));
        $nodeTraverser->addVisitor(new InterfaceExtends(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
<?php

interface TestInterface extends $extends
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideInterfaces
     * @param array $interfaces
     */
    public function it_generates_class_extends_with_namespace_for_empty_file(array $interfaces): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));
        $nodeTraverser->addVisitor(new InterfaceExtends(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

interface TestInterface extends $extends
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideInterfaces
     * @param array $interfaces
     */
    public function it_generates_class_extends_for_namespace_file(array $interfaces): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));
        $nodeTraverser->addVisitor(new InterfaceExtends(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

interface TestInterface extends $extends
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideInterfaces
     * @param array $interfaces
     */
    public function it_checks_class_extends_with_namespace_for_existing_file(array $interfaces): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;

interface TestInterface {}
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new InterfaceFile(new InterfaceGenerator('TestInterface')));
        $nodeTraverser->addVisitor(new InterfaceExtends(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

interface TestInterface extends $extends
{
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
