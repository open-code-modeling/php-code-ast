<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use PhpParser\Node;

final class ClassConstGenerator extends AbstractMemberGenerator
{
    /**
     * @var ValueGenerator
     */
    private $value;

    /**
     * @param string $name
     * @param ValueGenerator|mixed $value
     * @param int $flags
     */
    public function __construct(string $name, $value, $flags = self::FLAG_PUBLIC)
    {
        $this->setName($name);
        $this->setValue($value);

        if ($flags !== self::FLAG_PUBLIC) {
            $this->setFlags($flags);
        }
    }

    /**
     * @param ValueGenerator|mixed $value
     * @param string $valueType
     *
     * @return ClassConstGenerator
     */
    public function setValue($value, $valueType = ValueGenerator::TYPE_AUTO): self
    {
        if (! $value instanceof ValueGenerator) {
            $value = new ValueGenerator($value, $valueType);
        }

        $this->value = $value;

        return $this;
    }

    public function getValue(): ValueGenerator
    {
        return $this->value;
    }

    public function generate(): \PhpParser\Node\Stmt\ClassConst
    {
        return new Node\Stmt\ClassConst(
            [
                new Node\Const_($this->name, $this->value->generate()),
            ],
            $this->flags
        );
    }
}
