<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use Countable;
use Iterator;

final class ClassBuilderCollection implements Iterator, Countable
{
    /**
     * @var array<string, ClassBuilder>
     */
    private $items;

    public static function fromItems(ClassBuilder ...$classBuilders): self
    {
        return new self(...$classBuilders);
    }

    public static function emptyList(): self
    {
        return new self();
    }

    private function __construct(ClassBuilder ...$classBuilders)
    {
        foreach ($classBuilders as $classBuilder) {
            $this->items[$this->identifier($classBuilder)] = $classBuilder;
        }
    }

    public function add(ClassBuilder $classBuilder): self
    {
        $this->items[$this->identifier($classBuilder)] = $classBuilder;

        return $this;
    }

    public function remove(ClassBuilder $classBuilder): self
    {
        unset($this->items[$this->identifier($classBuilder)]);

        return $this;
    }

    public function contains(ClassBuilder $classBuilder): bool
    {
        return isset($this->items[$this->identifier($classBuilder)]);
    }

    public function filter(callable $filter): self
    {
        return new self(...\array_values(
                \array_filter(
                    $this->items,
                    static function (ClassBuilder $classBuilder) use ($filter) {
                        return $filter($classBuilder);
                    }
                )
            )
        );
    }

    /**
     * @return array<string, ClassBuilder>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function rewind(): void
    {
        \reset($this->items);
    }

    public function current(): ClassBuilder
    {
        return \current($this->items);
    }

    public function key(): string
    {
        return \key($this->items);
    }

    public function next(): void
    {
        \next($this->items);
    }

    public function valid(): bool
    {
        return \key($this->items) !== null;
    }

    public function count(): int
    {
        return \count($this->items);
    }

    private function identifier(ClassBuilder $classBuilder): string
    {
        $namespace = $classBuilder->getNamespace() !== null ? ('\\' . $classBuilder->getNamespace()) : '';
        $name = $classBuilder->getName() !== null ? ('\\' . $classBuilder->getName()) : '';

        $identifier = $namespace . $name;

        if ($identifier === '') {
            return \spl_object_hash($classBuilder);
        }

        return $identifier;
    }
}
