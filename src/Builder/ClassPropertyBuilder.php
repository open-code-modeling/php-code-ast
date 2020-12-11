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
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;

final class ClassPropertyBuilder
{
    /** @var string */
    private string $name;

    /** @var string|null */
    private ?string $type;

    /** @var mixed */
    private $defaultValue;

    /**
     * @var int
     */
    private int $visibility;

    /** @var bool */
    private bool $typed = false;

    /**
     * @var string|null
     */
    private ?string $docBlockComment = null;

    /**
     * @var string|null
     */
    private ?string $typeDocBlockHint = null;

    /**
     * @var DocBlock|null
     */
    private ?DocBlock $docBlock = null;

    private function __construct()
    {
    }

    public static function fromNode(Node\Stmt\Property $node, bool $typed = true): self
    {
        $self = new self();

        $type = null;

        switch (true) {
            case $node->type instanceof Node\Name:
            case $node->type instanceof Node\Identifier:
                $type = $node->type->toString();
                break;
            case $node->type instanceof Node\NullableType:
                $type = '?' . $node->type->type->toString();
                break;
            default:
                break;
        }

        $self->name = $node->props[0]->name->name;
        $self->defaultValue = $node->props[0]->default;
        $self->type = $type;
        $self->visibility = $node->flags;
        $self->typed = $typed;

        $comments = $node->getAttribute('comments');

        if ($comments !== null
            && $comments[0] instanceof Doc
        ) {
            $comments = \explode("\n", $comments[0]->getReformattedText());

            foreach ($comments as $comment) {
                if (0 === \strpos($comment, ' * @var ')) {
                    $self->setTypeDocBlockHint(\substr($comment, 8));
                }
            }
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setTyped(bool $typed): self
    {
        $this->typed = $typed;

        return $this;
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

    public function isPrivate(): bool
    {
        return (bool) ($this->visibility & ClassConstGenerator::FLAG_PRIVATE);
    }

    public function isProtected(): bool
    {
        return (bool) ($this->visibility & ClassConstGenerator::FLAG_PROTECTED);
    }

    public function isPublic(): bool
    {
        return (bool) ($this->visibility & ClassConstGenerator::FLAG_PUBLIC);
    }

    public function getDocBlockComment(): ?string
    {
        return $this->docBlockComment;
    }

    public function setDocBlockComment(?string $docBlockComment): self
    {
        $this->docBlockComment = $docBlockComment;

        return $this;
    }

    public function getTypeDocBlockHint(): ?string
    {
        return $this->typeDocBlockHint;
    }

    public function setTypeDocBlockHint(?string $typeDocBlockHint): self
    {
        $this->typeDocBlockHint = $typeDocBlockHint;

        return $this;
    }

    public function getDocBlock(): ?DocBlock
    {
        return $this->docBlock;
    }

    public function overrideDocBlock(?DocBlock $docBlock): self
    {
        $this->docBlock = $docBlock;

        return $this;
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
