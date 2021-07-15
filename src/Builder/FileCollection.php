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

final class FileCollection implements Iterator, Countable
{
    /**
     * @var array<string, File>
     */
    private array $items;

    public static function fromItems(File ...$files): self
    {
        return new self(...$files);
    }

    public static function emptyList(): self
    {
        return new self();
    }

    private function __construct(File ...$files)
    {
        $this->items = [];

        foreach ($files as $file) {
            $this->items[$this->identifier($file)] = $file;
        }
    }

    public function add(File ...$files): self
    {
        foreach ($files as $file) {
            $this->items[$this->identifier($file)] = $file;
        }

        return $this;
    }

    public function remove(File ...$files): self
    {
        foreach ($files as $file) {
            unset($this->items[$this->identifier($file)]);
        }

        return $this;
    }

    public function contains(File $file): bool
    {
        return isset($this->items[$this->identifier($file)]);
    }

    public function addFileCollection(FileCollection $fileCollection): self
    {
        foreach ($fileCollection as $file) {
            $this->add($file);
        }

        return $this;
    }

    public function removeFileCollection(FileCollection $fileCollection): self
    {
        foreach ($fileCollection as $file) {
            $this->remove($file);
        }

        return $this;
    }

    public function filter(callable $filter): self
    {
        return new self(...\array_values(
                \array_filter(
                    $this->items,
                    static function (File $file) use ($filter) {
                        return $filter($file);
                    }
                )
            )
        );
    }

    /**
     * @return array<string, File>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function rewind(): void
    {
        \reset($this->items);
    }

    public function current(): File
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

    private function identifier(File $file): string
    {
        $identifier = '';

        if ($file instanceof PhpFile) {
            $namespace = $file->getNamespace() !== null ? ('\\' . $file->getNamespace()) : '';
            $name = $file->getName() !== null ? ('\\' . $file->getName()) : '';

            $identifier = $namespace . $name;
        }

        if ($identifier === '') {
            return \spl_object_hash($file);
        }

        return $identifier;
    }
}
