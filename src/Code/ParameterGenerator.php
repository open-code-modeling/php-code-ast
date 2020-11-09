<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use PhpParser\Node;

/**
 * Code is largely lifted from the Zend\Code\Generator\ParameterGenerator implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class ParameterGenerator
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var TypeGenerator|null
     */
    private $type;

    /**
     * @var ValueGenerator
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

    /**
     * @param string $name
     * @param string|null $type
     * @param mixed $defaultValue
     * @param bool $passByReference
     */
    public function __construct(
        string $name,
        string $type = null,
        $defaultValue = null,
        bool $passByReference = false
    ) {
        $this->setName($name);

        if (null !== $type) {
            $this->setType($type);
        }
        if (null !== $defaultValue) {
            $this->setDefaultValue($defaultValue);
        }
        if (false !== $passByReference) {
            $this->setPassedByReference(true);
        }
    }

    /**
     * @param  string $type
     * @return ParameterGenerator
     */
    public function setType($type): self
    {
        $this->type = TypeGenerator::fromTypeString($type);

        return $this;
    }

    public function getType(): ?TypeGenerator
    {
        return $this->type;
    }

    /**
     * @param  string $name
     * @return ParameterGenerator
     */
    public function setName(string $name): self
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the default value of the parameter.
     *
     * Certain variables are difficult to express
     *
     * @param  null|bool|string|int|float|array|ValueGenerator $defaultValue
     * @return ParameterGenerator
     */
    public function setDefaultValue($defaultValue): self
    {
        if (! $defaultValue instanceof ValueGenerator) {
            $defaultValue = new ValueGenerator($defaultValue);
        }
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * @return ValueGenerator
     */
    public function getDefaultValue(): ValueGenerator
    {
        return $this->defaultValue;
    }

    /**
     * @return bool
     */
    public function getPassedByReference(): bool
    {
        return $this->passedByReference;
    }

    /**
     * @param  bool $passedByReference
     * @return ParameterGenerator
     */
    public function setPassedByReference($passedByReference): self
    {
        $this->passedByReference = (bool) $passedByReference;

        return $this;
    }

    /**
     * @param bool $variadic
     *
     * @return ParameterGenerator
     */
    public function setVariadic($variadic): self
    {
        $this->variadic = (bool) $variadic;

        return $this;
    }

    /**
     * @return bool
     */
    public function getVariadic(): bool
    {
        return $this->variadic;
    }

    /**
     * @return string
     */
    public function getTypeDocBlockHint(): ?string
    {
        return $this->typeDocBlockHint;
    }

    /**
     * @param string|null $typeDocBlockHint
     */
    public function setTypeDocBlockHint(?string $typeDocBlockHint): void
    {
        $this->typeDocBlockHint = $typeDocBlockHint;
    }

    public function generate(): Node\Param
    {
        return new Node\Param(
            new Node\Expr\Variable($this->name),
            $this->defaultValue ? $this->defaultValue->generate() : null,
            $this->type ? $this->type->generate() : null,
            $this->passedByReference,
            $this->variadic
        );
    }
}
