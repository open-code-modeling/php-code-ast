<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassConstant;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;

final class ClassConstBuilder
{
    use VisibilityTrait;

    /** @var string */
    private string $name;

    /** @var mixed */
    private $value;

    private function __construct()
    {
    }

    public static function fromNode(Node\Stmt\ClassConst $node): self
    {
        $self = new self();

        $self->name = $node->consts[0]->name->name;

        if ($node->consts[0]->value instanceof Node\Scalar) {
            $self->value = $node->consts[0]->value->value;
        } else {
            // use node expression
            $self->value = $node->consts[0]->value;
        }

        $self->visibility = $node->flags;

        return $self;
    }

    public static function fromScratch(string $name, $value, $visibility = ClassConstGenerator::FLAG_PUBLIC): self
    {
        $self = new self();
        $self->name = $name;
        $self->value = $value;
        $self->visibility = $visibility;

        return $self;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function generate(): NodeVisitor
    {
        return new ClassConstant(
            new IdentifierGenerator(
                $this->name,
                new ClassConstGenerator($this->name, $this->value, $this->visibility)
            )
        );
    }

    public function injectVisitors(NodeTraverser $nodeTraverser): void
    {
        $nodeTraverser->addVisitor($this->generate());
    }
}
