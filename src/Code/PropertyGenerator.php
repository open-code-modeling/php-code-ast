<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

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
     * @var ValueGenerator
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $omitDefaultValue = false;

    /**
     * @param string $name
     * @param ValueGenerator|string|array $defaultValue
     * @param int $flags
     */
    public function __construct($name = null, $defaultValue = null, $flags = self::FLAG_PRIVATE)
    {
        if (null !== $name) {
            $this->setName($name);
        }
        if (null !== $defaultValue) {
            $this->setDefaultValue($defaultValue);
        }
        if ($flags !== self::FLAG_PUBLIC) {
            $this->setFlags($flags);
        }
    }

    /**
     * @param ValueGenerator|mixed $defaultValue
     * @param string                       $defaultValueType
     *
     * @return PropertyGenerator
     */
    public function setDefaultValue(
        $defaultValue,
        $defaultValueType = ValueGenerator::TYPE_AUTO
    ): self {
        if (! $defaultValue instanceof ValueGenerator) {
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
        return new Property(
            $this->flags,
            [
                new Node\Stmt\PropertyProperty(
                    $this->name,
                    $this->defaultValue ? $this->defaultValue->generate() : null
                ),
            ]
        );
    }
}
