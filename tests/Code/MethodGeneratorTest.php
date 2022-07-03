<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Code;

use OpenCodeModeling\CodeAst\Code\AttributeGenerator;
use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\DocBlock\DocBlock;
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use OpenCodeModeling\CodeAst\Exception\RuntimeException;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class MethodGeneratorTest extends TestCase
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
    public function it_generates_method_without_doc_block_if_typed(): void
    {
        $method = new MethodGenerator(
            'setType',
            [
                new ParameterGenerator('type', '?string'),
            ]
        );
        $method->setTyped(true);
        $method->setReturnType('void');

        $expectedOutput = <<<'EOF'
        <?php
        
        public function setType(?string $type) : void;
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_doc_block_if_typed(): void
    {
        $method = new MethodGenerator('getItems');
        $method->setReturnTypeDocBlockHint('Items[]');
        $method->setReturnType('array');

        $expectedOutput = <<<'EOF'
        <?php
        
        /**
         * @return Items[]
         */
        public function getItems() : array;
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_variadic_doc_block(): void
    {
        $method = new MethodGenerator('setItems');
        $method->setParameter((new ParameterGenerator('items'))->setVariadic(true)->setTypeDocBlockHint('mixed'));
        $method->setReturnType('void');
        $method->setDocBlockComment('');

        $expectedOutput = <<<'EOF'
        <?php
        
        /**
         * @param mixed ...$items
         */
        public function setItems(...$items) : void;
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_mixed_variadic_without_doc_block(): void
    {
        $method = new MethodGenerator('setItems');
        $method->setParameter((new ParameterGenerator('items'))->setVariadic(true)->setType('mixed'));
        $method->setReturnType('void');

        $expectedOutput = <<<'EOF'
        <?php
        
        public function setItems(mixed ...$items) : void;
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_overridden_doc_block(): void
    {
        $method = new MethodGenerator('getItems');
        $method->setReturnTypeDocBlockHint('Items[]');
        $method->setReturnType('array');
        $method->overrideDocBlock(new DocBlock('Awesome'));

        $expectedOutput = <<<'EOF'
        <?php
        
        /**
         * Awesome
         */
        public function getItems() : array;
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_doc_block_comment_if_typed(): void
    {
        $method = new MethodGenerator(
            'setType',
            [
                new ParameterGenerator('type', '?string'),
            ]
        );
        $method->setTyped(true);
        $method->setDocBlockComment('Sets an awesome type');

        $expectedOutput = <<<'EOF'
        <?php
        
        /**
         * Sets an awesome type
         *
         * @param string|null $type
         */
        public function setType(?string $type);
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_doc_block_comment(): void
    {
        $method = new MethodGenerator(
            'setType',
            [
                new ParameterGenerator('type', '?string'),
            ]
        );
        $method->setReturnType('void');
        $method->setReturnTypeDocBlockHint('void');
        $method->setDocBlockComment('Sets an awesome type');
        $method->setTyped(true);

        $expectedOutput = <<<'EOF'
        <?php
        
        /**
         * Sets an awesome type
         *
         * @param string|null $type
         * @return void
         */
        public function setType(?string $type) : void;
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_mixed_type_doc_block(): void
    {
        $method = new MethodGenerator(
            'setType',
            [
                new ParameterGenerator('type'),
            ]
        );
        $method->setReturnTypeDocBlockHint('mixed');

        $expectedOutput = <<<'EOF'
        <?php
        
        /**
         * @param mixed $type
         * @return mixed
         */
        public function setType($type);
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_array_type_doc_block(): void
    {
        $parameter = new ParameterGenerator('items', 'array');
        $parameter->setTypeDocBlockHint('array<string, \stdClass>');

        $method = new MethodGenerator(
            'setItems',
            [
                $parameter,
            ]
        );
        $method->setReturnType('void');
        $method->setReturnTypeDocBlockHint('void');
        $method->setDocBlockComment('Sets awesome items');

        $expectedOutput = <<<'EOF'
        <?php
        
        /**
         * Sets awesome items
         *
         * @param array<string, \stdClass> $items
         * @return void
         */
        public function setItems(array $items) : void;
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_generates_method_with_long_doc_block(): void
    {
        $docBlockComment = <<<'EOF'
        Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's
        standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a
        type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
        remaining essentially unchanged.
        
        It is a long established fact that a reader will be distracted by the readable content of a page when looking at
        its layout.
        EOF;

        $method = new MethodGenerator(
            'setType',
            [
                new ParameterGenerator('type', 'string'),
                new ParameterGenerator('value', '?int'),
            ]
        );
        $method->setDocBlockComment($docBlockComment);

        $expectedOutput = <<<'EOF'
        <?php
        
        /**
         * Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's
         * standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a
         * type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
         * remaining essentially unchanged.
         *
         * It is a long established fact that a reader will be distracted by the readable content of a page when looking at
         * its layout.
         *
         * @param string $type
         * @param int|null $value
         */
        public function setType(string $type, ?int $value);
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }

    /**
     * @test
     */
    public function it_catches_body_parse_errors_and_throws_runtime_exception(): void
    {
        $method = new MethodGenerator(
            'setType',
            [
                new ParameterGenerator('type', '?string'),
            ],
            MethodGenerator::FLAG_PUBLIC,
            new BodyGenerator($this->parser, '$test=1; error=true')
        );
        $method->setTyped(true);
        $method->setReturnType('void');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not generate method "setType" due the following error');

        $this->printer->prettyPrintFile([$method->generate()]);
    }

    /**
     * @test
     */
    public function it_generates_method_with_readonly_properties(): void
    {
        $arg1 = new ParameterGenerator('type', 'string');
        $arg1->setReadonly(true);
        $arg1->setVisibility(ParameterGenerator::VISIBILITY_PUBLIC);
        $arg1->addAttribute(new AttributeGenerator($this->parser, 'ListensTo', 'ProductCreated::class', "'string value'"));

        $arg2 = new ParameterGenerator('option', '?string');
        $arg2->setVisibility(ParameterGenerator::VISIBILITY_PROTECTED);

        $method = new MethodGenerator(
            '__construct',
            [
                $arg1,
                $arg2,
            ]
        );
        $method->setTyped(true);

        $expectedOutput = <<<'EOF'
        <?php
        
        public function __construct(#[ListensTo(ProductCreated::class, 'string value')] public readonly string $type, protected ?string $option);
        EOF;

        $this->assertSame($expectedOutput, $this->printer->prettyPrintFile([$method->generate()]));
    }
}
