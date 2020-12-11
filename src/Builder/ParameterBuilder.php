<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Code\ParameterGenerator;
use PhpParser\Node;

final class ParameterBuilder
{
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

        return $self;
    }

    public static function fromScratch(
        string $name,
        ?string $type = null,
        $defaultValue = null
    ): self {
        $self = new self();
        $self->name = $name;
        $self->type = $type;
        $self->defaultValue = $defaultValue;

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

    public function generate(): ParameterGenerator
    {
        $methodGenerator = new ParameterGenerator($this->name, $this->type, $this->defaultValue, $this->passedByReference);

        $methodGenerator->setTypeDocBlockHint($this->typeDocBlockHint);
        $methodGenerator->setVariadic($this->variadic);

        return $methodGenerator;
    }
}
