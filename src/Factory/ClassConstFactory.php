<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Factory;

use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassConstant;
use PhpParser\Node;
use PhpParser\NodeVisitor;

final class ClassConstFactory
{
    /** @var string */
    private $name;

    /** @var mixed */
    private $value;

    /**
     * @var int
     */
    private $visibility;

    private function __construct()
    {
    }

    public static function fromNode(Node\Stmt\ClassConst $node): self
    {
        $self = new self();

        $self->name = $node->consts[0]->name->name;
        // @phpstan-ignore-next-line
        $self->value = $node->consts[0]->value->value;
        $self->visibility = $node->flags;

        return $self;
    }

    public static function fromScratch(string $name, $value): self
    {
        $self = new self();
        $self->name = $name;
        $self->value = $value;

        return $self;
    }

    public function setPrivate(): self
    {
        $this->visibility = ClassConstGenerator::FLAG_PRIVATE;

        return $this;
    }

    public function setProtected(): self
    {
        $this->visibility = ClassConstGenerator::FLAG_PROTECTED;

        return $this;
    }

    public function setPublic(): self
    {
        $this->visibility = ClassConstGenerator::FLAG_PUBLIC;

        return $this;
    }

    public function generate(): NodeVisitor
    {
        return ClassConstant::forClassConstant($this->name, $this->value, $this->visibility);
    }
}
