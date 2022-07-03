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
use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use PhpParser\Node;
use PhpParser\Parser;

final class ParameterBuilder
{
    use ReadonlyTrait;
    use VisibilityTrait;
    use AttributeTrait;

    /** @var string */
    private string $name;

    /** @var string|null */
    private ?string $type = null;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private bool $passedByReference = false;

    /**
     * @var bool
     */
    private bool $variadic = false;

    /**
     * @var string|null
     */
    private ?string $typeDocBlockHint = null;

    private ParameterGenerator $parameterGenerator;

    private function __construct()
    {
    }

    public static function fromNode(Node\Param $node): self
    {
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

        $self = new self();
        $self->name = $node->var->name;
        $self->type = $type;
        $self->variadic = $node->variadic;
        $self->passedByReference = $node->byRef;
        $self->parameterGenerator = new ParameterGenerator($self->name, $self->type);

        $defaultValue = $node->default;

        switch (true) {
            case $defaultValue instanceof Node\Expr\ConstFetch:
                $self->defaultValue = $defaultValue->name->toString();

                if ($self->defaultValue === 'null') {
                    $self->defaultValue = null;
                }
                $self->parameterGenerator->setDefaultValue($self->defaultValue);
                break;
            case $defaultValue instanceof Node\Expr\ClassConstFetch:
                $self->defaultValue = $defaultValue->class->toString() . '::'.  $defaultValue->name->toString();
                $self->parameterGenerator->setDefaultValue($self->defaultValue);
                break;
            default:
                if ($defaultValue !== null) {
                    $self->defaultValue = $defaultValue;
                    $self->parameterGenerator->setDefaultValue($self->defaultValue);
                }
                break;
        }

        return $self;
    }

    public static function fromScratch(string $name, ?string $type = null): self
    {
        $self = new self();
        $self->name = $name;
        $self->type = $type;
        $self->parameterGenerator = new ParameterGenerator($self->name, $self->type);

        return $self;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        $this->parameterGenerator->setDefaultValue($this->defaultValue);

        return $this;
    }

    public function isPassedByReference(): bool
    {
        return $this->passedByReference;
    }

    public function setPassedByReference(bool $passedByReference): self
    {
        $this->passedByReference = $passedByReference;

        return $this;
    }

    public function isVariadic(): bool
    {
        return $this->variadic;
    }

    public function setVariadic(bool $variadic): self
    {
        $this->variadic = $variadic;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
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

    public function generate(Parser $parser): ParameterGenerator
    {
        $this->parameterGenerator->setName($this->name);
        $this->parameterGenerator->setType($this->type);
        $this->parameterGenerator->setPassedByReference($this->passedByReference);
        $this->parameterGenerator->setTypeDocBlockHint($this->typeDocBlockHint);
        $this->parameterGenerator->setVariadic($this->variadic);

        if ($this->isReadonly) {
            $this->parameterGenerator->addFlag(AbstractMemberGenerator::FLAG_READONLY);
        }
        if ($this->visibility) {
            $this->parameterGenerator->addFlag($this->visibility);
        }

        $this->parameterGenerator->setAttributes(
            ...\array_map(static fn (AttributeBuilder $attribute) => new AttributeGenerator($parser, $attribute->getname(), ...$attribute->getArgs()), $this->attributes)
        );

        return $this->parameterGenerator;
    }
}
