<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use PHPUnit\Framework\TestCase;

final class FileCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_adds_same_class_builder_not_twice(): void
    {
        $classBuilder = ClassBuilder::fromScratch('MyClass');
        $cut = FileCollection::fromItems($classBuilder);
        $this->assertCount(1, $cut);

        $cut->add($classBuilder);
        $this->assertCount(1, $cut);

        $items = $cut->items();
        $this->assertArrayHasKey('\\MyClass', $items);
    }

    /**
     * @test
     */
    public function it_adds_and_removes_class_builder(): void
    {
        $classBuilderWithoutNamespace = ClassBuilder::fromScratch('MyClass');
        $classBuilderNamespace = ClassBuilder::fromScratch('MyClass', 'MyNamespace');

        $cut = FileCollection::fromItems($classBuilderWithoutNamespace);
        $this->assertCount(1, $cut);

        $cut->add($classBuilderNamespace);
        $this->assertCount(2, $cut);

        $items = $cut->items();
        $this->assertArrayHasKey('\\MyClass', $items);
        $this->assertArrayHasKey('\\MyNamespace\\MyClass', $items);

        $i = 0;

        foreach ($cut as $item) {
            $this->assertInstanceOf(ClassBuilder::class, $item);
            if ($i === 0) {
                $this->assertSame('MyClass', $item->getName());
                $this->assertNull($item->getNamespace());
            } else {
                $this->assertSame('MyClass', $item->getName());
                $this->assertSame('MyNamespace', $item->getNamespace());
            }
            $i++;
        }

        $cut->remove($classBuilderNamespace);
        $this->assertCount(1, $cut);

        $items = $cut->items();
        $this->assertArrayHasKey('\\MyClass', $items);
    }

    /**
     * @test
     */
    public function it_adds_anonymous_class_builder(): void
    {
        $classBuilder = ClassBuilder::fromScratch(null);

        $cut = FileCollection::fromItems($classBuilder);
        $this->assertCount(1, $cut);

        $items = $cut->items();
        $this->assertArrayHasKey(\spl_object_hash($classBuilder), $items);
    }

    /**
     * @test
     */
    public function it_can_be_empty(): void
    {
        $cut = FileCollection::emptyList();
        $this->assertCount(0, $cut);

        $this->assertSame([], $cut->items());
    }
}
