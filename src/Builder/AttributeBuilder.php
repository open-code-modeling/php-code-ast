<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

final class AttributeBuilder
{
    /**
     * @var string
     */
    private string $name;
    private array $args;

    private function __construct(string $name, ...$args)
    {
        $this->name = $name;
        $this->args = $args;
    }

    public static function fromScratch(string $name, ...$args): self
    {
        return new self($name, ...$args);
    }

    public static function fromNode(Attribute $attribute, PrettyPrinterAbstract $printer = null): self
    {
        if (null === $printer) {
            $printer = new Standard(['shortArraySyntax' => true]);
        }

        return new self($attribute->name->toString(), ...\array_map(static fn (Arg $arg) => $printer->prettyPrint([$arg]), $attribute->args));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(...$args): void
    {
        $this->args = $args;
    }
}
