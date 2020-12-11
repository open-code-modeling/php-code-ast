<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\NodeVisitor;

use Generator;
use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\Property;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class PropertyTest extends TestCase
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
     * Values are: type
     *
     * @return Generator
     */
    public function provideTypes(): Generator
    {
        yield 'string' => ['string'];
        yield 'bool' => ['bool'];
        yield 'int' => ['int'];
        yield 'float' => ['float'];
        yield '\\Awesome\\AcmeClass' => ['\\Awesome\\AcmeClass'];
        yield '\\Foo' => ['\\Foo'];

        yield 'nullable string' => ['?string'];
        yield 'nullable bool' => ['?bool'];
        yield 'nullable int' => ['?int'];
        yield 'nullable float' => ['?float'];
        yield 'nullable \\Awesome\\AcmeClass' => ['?\\Awesome\\AcmeClass'];
        yield 'nullable \\Foo' => ['?\\Foo'];
    }

    /**
     * @test
     * @dataProvider provideTypes
     * @param string $type
     */
    public function it_generates_property_for_class_for_empty_file(string $type): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(Property::forClassProperty('property', $type, null, true));

        $expected = <<<EOF
<?php

class TestClass
{
    private $type \$property;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideTypes
     * @param string $type
     */
    public function it_generates_property_for_class_for_existing_file(string $type): void
    {
        $ast = $this->parser->parse('<?php class TestClass {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(Property::forClassProperty('property', $type, null, true));

        $expected = <<<EOF
<?php

class TestClass
{
    private $type \$property;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideTypes
     * @param string $type
     */
    public function it_generates_property_for_class_with_namespace_for_empty_file(string $type): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(Property::forClassProperty('property', $type, null, true));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass
{
    private $type \$property;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideTypes
     * @param string $type
     */
    public function it_generates_property_for_class_with_namespace_for_existing_file(string $type): void
    {
        $code = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass {}
EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(Property::forClassProperty('property', $type, null, true));

        $expected = <<<EOF
<?php

namespace My\Awesome\Service;

class TestClass
{
    private $type \$property;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
