<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Code\DocBlock\DocBlock;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\Property;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;

final class ClassPropertyBuilder
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var mixed */
    private $defaultValue;

    /**
     * @var int
     */
    private $visibility;

    /** @var bool */
    private $typed = false;

    /**
     * @var string|null
     */
    private $docBlockComment;

    /**
     * @var string|null
     */
    private $typeDocBlockHint;

    /**
     * @var DocBlock|null
     */
    private $docBlock;

    private function __construct()
    {
    }

    public static function fromNode(Node\Stmt\Property $node): self
    {
        $self = new self();

        $self->name = $node->props[0]->name->name;
        $self->defaultValue = $node->props[0]->default;
        $self->type = $node->type ? $node->type->toString() : null;
        $self->visibility = $node->flags;

        if ($self->type !== null) {
            $self->typed = true;
        }

        return $self;
    }

    public static function fromScratch(string $name, string $type, bool $typed = true): self
    {
        $self = new self();
        $self->name = $name;
        $self->type = $type;
        $self->typed = $typed;
        $self->visibility = ClassConstGenerator::FLAG_PRIVATE;

        return $self;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isTyped(): bool
    {
        return $this->typed;
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

    public function getDocBlockComment(): ?string
    {
        return $this->docBlockComment;
    }

    public function setDocBlockComment(?string $docBlockComment): void
    {
        $this->docBlockComment = $docBlockComment;
    }

    public function getTypeDocBlockHint(): string
    {
        return $this->typeDocBlockHint;
    }

    public function setTypeDocBlockHint(?string $typeDocBlockHint): void
    {
        $this->typeDocBlockHint = $typeDocBlockHint;
    }

    public function getDocBlock(): ?DocBlock
    {
        return $this->docBlock;
    }

    public function overrideDocBlock(?DocBlock $docBlock): void
    {
        $this->docBlock = $docBlock;
    }

    public function generate(): NodeVisitor
    {
        return new Property($this->propertyGenerator());
    }

    private function propertyGenerator(): PropertyGenerator
    {
        $flags = $this->visibility;

        $propertyGenerator = new PropertyGenerator($this->name, $this->type, $this->defaultValue, $this->typed, $flags);

        $propertyGenerator->setDocBlockComment($this->docBlockComment);
        $propertyGenerator->setTypeDocBlockHint($this->typeDocBlockHint);
        $propertyGenerator->overrideDocBlock($this->docBlock);

        return $propertyGenerator;
    }

    public function injectVisitors(NodeTraverser $nodeTraverser): void
    {
        $nodeTraverser->addVisitor($this->generate());
    }
}
