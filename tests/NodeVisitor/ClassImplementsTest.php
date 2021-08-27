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
use OpenCodeModeling\CodeAst\NodeVisitor\ClassImplements;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassImplementsTest extends TestCase
{
    private Parser $parser;

    private Standard $printer;

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
    public function provideImplements(): Generator
    {
        yield '\\Awesome\\AcmeClass' => [['\\Awesome\\AcmeClass']];
        yield '\\Foo' => [['\\Foo']];

        yield '\\Awesome\\AcmeClass, \\My\\OtherInterface' => [['\\Awesome\\AcmeClass', '\\My\\OtherInterface']];
        yield '\\Foo, \\Bar' => [['\\Foo', '\\Bar']];

        yield 'FirstInterface, SecondInterface, ThirdInterface' => [['FirstInterface', 'SecondInterface', 'ThirdInterface']];
    }

    /**
     * @test
     * @dataProvider provideImplements
     * @param array $interfaces
     */
    public function it_generates_class_implements_for_empty_file(array $interfaces): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassImplements(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
        <?php
        
        class TestClass implements $extends
        {
        }
        EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));

        $ast = $this->parser->parse($expected);
        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideImplements
     * @param array $interfaces
     */
    public function it_checks_class_implements_for_existing_file(array $interfaces): void
    {
        $ast = $this->parser->parse('<?php class TestClass {}');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassImplements(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
        <?php
        
        class TestClass implements $extends
        {
        }
        EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));

        $ast = $this->parser->parse($expected);
        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideImplements
     * @param array $interfaces
     */
    public function it_generates_class_implements_with_namespace_for_empty_file(array $interfaces): void
    {
        $ast = $this->parser->parse('');

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassNamespace('My\\Awesome\\Service'));
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassImplements(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
        <?php
        
        namespace My\Awesome\Service;
        
        class TestClass implements $extends
        {
        }
        EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));

        $ast = $this->parser->parse($expected);
        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideImplements
     * @param array $interfaces
     */
    public function it_generates_class_implements_for_namespace_file(array $interfaces): void
    {
        $code = <<<EOF
        <?php
        
        namespace My\Awesome\Service;
        EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassImplements(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
        <?php
        
        namespace My\Awesome\Service;
        
        class TestClass implements $extends
        {
        }
        EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));

        $ast = $this->parser->parse($expected);
        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     * @dataProvider provideImplements
     * @param array $interfaces
     */
    public function it_checks_class_implements_with_namespace_for_existing_file(array $interfaces): void
    {
        $code = <<<EOF
        <?php
        
        namespace My\Awesome\Service;
        
        class TestClass {}
        EOF;

        $ast = $this->parser->parse($code);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new ClassFile(new ClassGenerator('TestClass')));
        $nodeTraverser->addVisitor(new ClassImplements(...$interfaces));

        $extends = \implode(', ', $interfaces);

        $expected = <<<EOF
        <?php
        
        namespace My\Awesome\Service;
        
        class TestClass implements $extends
        {
        }
        EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));

        $ast = $this->parser->parse($expected);
        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }
}
