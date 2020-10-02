<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property;

/**
 * Code is largely lifted from the Zend\Code\Generator\PropertyGenerator implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class PropertyGenerator extends AbstractMemberGenerator
{
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
    private $typed = false;

    public function __construct(
        string $name = null,
        string $type = null,
        $defaultValue = null,
        bool $typed = false,
        int $flags = self::FLAG_PRIVATE
    ) {
        if (null !== $name) {
            $this->setName($name);
        }

        if (null !== $type) {
            $this->setType($type);
        }

        if (null !== $defaultValue) {
            $this->setDefaultValue($defaultValue);
        }

        $this->typed = $typed;

        if ($flags !== self::FLAG_PUBLIC) {
            $this->setFlags($flags);
        }
    }

    /**
     * @param string $type
     * @return ParameterGenerator
     */
    public function setType($type): self
    {
        $this->type = TypeGenerator::fromTypeString($type);

        return $this;
    }

    public function getType(): TypeGenerator
    {
        return $this->type;
    }

    /**
     * @param ValueGenerator|mixed $defaultValue
     * @param string $defaultValueType
     *
     * @return PropertyGenerator
     */
    public function setDefaultValue(
        $defaultValue,
        $defaultValueType = ValueGenerator::TYPE_AUTO
    ): self {
        if (!$defaultValue instanceof ValueGenerator) {
            $defaultValue = new ValueGenerator($defaultValue, $defaultValueType);
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

    public function generate(): Property
    {
        $propComment = <<<EOF
/**
 * @var {$this->type->type()}
 */
EOF;
        $attributes = [];

        if ($this->typed === false) {
            $attributes = ['comments' => [new Doc($propComment)]];
        }

        return new Property(
            $this->flags,
            [
                new Node\Stmt\PropertyProperty(
                    $this->name,
                    $this->defaultValue ? $this->defaultValue->generate() : null
                ),
            ],
            $attributes,
            $this->typed ? $this->type->type() : null
        );
    }
}
