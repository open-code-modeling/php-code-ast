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
    private $name;

    /** @var string|null */
    private $type;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $passedByReference = false;

    /**
     * @var bool
     */
    private $variadic = false;

    /**
     * @var string|null
     */
    private $typeDocBlockHint;

    private function __construct()
    {
    }

    public static function fromNode(Node\Param $node): self
    {
        $self = new self();
        $self->name = $node->var->name;
        $self->type = $node->type ? $node->type->toString() : null;
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
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function isPassedByReference(): bool
    {
        return $this->passedByReference;
    }

    public function setPassedByReference(bool $passedByReference): void
    {
        $this->passedByReference = $passedByReference;
    }

    public function isVariadic(): bool
    {
        return $this->variadic;
    }

    public function setVariadic(bool $variadic): void
    {
        $this->variadic = $variadic;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTypeDocBlockHint(): string
    {
        return $this->typeDocBlockHint;
    }

    public function setTypeDocBlockHint(?string $typeDocBlockHint): void
    {
        $this->typeDocBlockHint = $typeDocBlockHint;
    }

    public function generate(): ParameterGenerator
    {
        $methodGenerator = new ParameterGenerator($this->name, $this->type, $this->defaultValue, $this->passedByReference);

        $methodGenerator->setTypeDocBlockHint($this->typeDocBlockHint);
        $methodGenerator->setVariadic($this->variadic);

        return $methodGenerator;
    }
}
