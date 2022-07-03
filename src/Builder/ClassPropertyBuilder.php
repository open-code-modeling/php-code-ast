<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Code\AbstractMemberGenerator;
use OpenCodeModeling\CodeAst\Code\AttributeGenerator;
use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Code\DocBlock\DocBlock;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\Property;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;

final class ClassPropertyBuilder
{
    use AttributeTrait;
    use ReadonlyTrait;
    use TypedTrait;
    use VisibilityTrait;

    /** @var string */
    private string $name;

    /** @var string|null */
    private ?string $type;

    /** @var mixed */
    private $defaultValue = null;

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

    private PropertyGenerator $propertyGenerator;

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
        $self->type = $type;
        $self->visibility = $node->flags;
        $self->typed = $typed;
        $self->isReadonly = $node->isReadonly();
        $self->propertyGenerator = new PropertyGenerator($self->name, $self->type, $typed);

        $defaultValue = $node->props[0]->default;

        switch (true) {
            case $defaultValue instanceof Node\Expr\ConstFetch:
                $self->defaultValue = $defaultValue->name->toString();

                if ($self->defaultValue === 'null') {
                    $self->defaultValue = null;
                }
                $self->propertyGenerator->setDefaultValue($self->defaultValue);
                break;
            case $defaultValue instanceof Node\Expr\ClassConstFetch:
                $self->defaultValue = $defaultValue->class->toString() . '::'.  $defaultValue->name->toString();
                $self->propertyGenerator->setDefaultValue($self->defaultValue);
                break;
            default:
                if ($defaultValue !== null) {
                    $self->defaultValue = $defaultValue;
                    $self->propertyGenerator->setDefaultValue($self->defaultValue);
                }
                break;
        }

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

        $attributes = [];

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attributes[] = AttributeBuilder::fromNode($attr);
            }
        }

        $self->attributes = $attributes;

        return $self;
    }

    public static function fromScratch(string $name, string $type, bool $typed = true): self
    {
        $self = new self();
        $self->name = $name;
        $self->type = $type;
        $self->typed = $typed;
        $self->visibility = ClassConstGenerator::FLAG_PRIVATE;
        $self->propertyGenerator = new PropertyGenerator($self->name, $self->type, $typed);

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

    public function setType(string $type): self
    {
        $this->type = $type;

        $this->propertyGenerator->setType($type);

        return $this;
    }

    public function setTyped(bool $typed): self
    {
        $this->typed = $typed;

        $this->propertyGenerator->setTyped($typed);

        return $this;
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

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
        $this->propertyGenerator->setDefaultValue($this->defaultValue);
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function generate(Parser $parser): NodeVisitor
    {
        return new Property($this->propertyGenerator($parser));
    }

    private function propertyGenerator(Parser $parser): PropertyGenerator
    {
        $flags = $this->visibility;

        if ($this->isReadonly) {
            $flags |= AbstractMemberGenerator::FLAG_READONLY;
        }

        $this->propertyGenerator->setFlags($flags);
        $this->propertyGenerator->setDocBlockComment($this->docBlockComment);
        $this->propertyGenerator->setTypeDocBlockHint($this->typeDocBlockHint);
        $this->propertyGenerator->overrideDocBlock($this->docBlock);
        $this->propertyGenerator->setAttributes(
            ...\array_map(static fn (AttributeBuilder $attribute) => new AttributeGenerator($parser, $attribute->getname(), ...$attribute->getArgs()), $this->attributes)
        );

        return $this->propertyGenerator;
    }

    public function injectVisitors(NodeTraverser $nodeTraverser, Parser $parser): void
    {
        $nodeTraverser->addVisitor($this->generate($parser));
    }
}
