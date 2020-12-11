<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use OpenCodeModeling\CodeAst\Exception;

use PhpParser\Node;

/**
 * Code is largely lifted from the Zend\Code\Generator\ValueGenerator implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class ValueGenerator
{
    /**#@+
     * Constant values
     */
    public const TYPE_AUTO = 'auto';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_BOOL = 'bool';
    public const TYPE_NUMBER = 'number';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_INT = 'int';
    public const TYPE_FLOAT = 'float';
    public const TYPE_DOUBLE = 'double';
    public const TYPE_STRING = 'string';
    public const TYPE_ARRAY = 'array';
    public const TYPE_ARRAY_SHORT = 'array_short';
    public const TYPE_ARRAY_LONG = 'array_long';
    public const TYPE_CONSTANT = 'constant';
    public const TYPE_NULL = 'null';
    public const TYPE_OBJECT = 'object';
    public const TYPE_OTHER = 'other';
    /**#@-*/

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private string $type = self::TYPE_AUTO;

    /**
     * @param mixed $value
     * @param string $type
     */
    public function __construct(
        $value,
        string $type = self::TYPE_AUTO
    ) {
        // strict check is important here if $type = AUTO
        $this->setValue($value);

        if ($type !== self::TYPE_AUTO) {
            $this->setType($type);
        }
    }

    /**
     * @param mixed $value
     * @return ValueGenerator
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $type
     * @return ValueGenerator
     */
    public function setType($type): self
    {
        $this->type = (string) $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return string
     */
    private function getValidatedType($type)
    {
        $types = [
            self::TYPE_AUTO,
            self::TYPE_BOOLEAN,
            self::TYPE_BOOL,
            self::TYPE_NUMBER,
            self::TYPE_INTEGER,
            self::TYPE_INT,
            self::TYPE_FLOAT,
            self::TYPE_DOUBLE,
            self::TYPE_STRING,
            self::TYPE_ARRAY,
            self::TYPE_ARRAY_SHORT,
            self::TYPE_ARRAY_LONG,
            self::TYPE_CONSTANT,
            self::TYPE_NULL,
            self::TYPE_OBJECT,
            self::TYPE_OTHER,
        ];

        if (\in_array($type, $types)) {
            return $type;
        }

        return self::TYPE_AUTO;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function getAutoDeterminedType($value): string
    {
        switch (\gettype($value)) {
            case 'boolean':
                return self::TYPE_BOOLEAN;
            case 'string':
                return self::TYPE_STRING;
            case 'double':
                return self::TYPE_DOUBLE;
            case 'float':
                return self::TYPE_FLOAT;
            case 'integer':
                return self::TYPE_NUMBER;
            case 'array':
                return self::TYPE_ARRAY;
            case 'NULL':
                return self::TYPE_NULL;
            case 'object':
            case 'resource':
            case 'unknown type':
            default:
                return self::TYPE_OTHER;
        }
    }

    public function generate(): Node\Expr
    {
        $type = $this->type;
        if ($type !== self::TYPE_AUTO) {
            $type = $this->getValidatedType($type);
        }
        $value = $this->value;
        if ($type === self::TYPE_AUTO) {
            $type = $this->getAutoDeterminedType($value);
        }

        switch ($type) {
            case self::TYPE_NULL:
                return new Node\Expr\ConstFetch(new Node\Name('null'));
            case self::TYPE_BOOLEAN:
            case self::TYPE_BOOL:
                return new Node\Expr\ConstFetch(new Node\Name($this->value ? 'true' : 'false'));
            case self::TYPE_STRING:
                return new Node\Scalar\String_($this->value);
            case self::TYPE_NUMBER:
            case self::TYPE_INTEGER:
            case self::TYPE_INT:
                return new Node\Scalar\LNumber($this->value);
            case self::TYPE_FLOAT:
            case self::TYPE_DOUBLE:
                return new Node\Scalar\DNumber($this->value);
            case self::TYPE_ARRAY:
            case self::TYPE_ARRAY_LONG:
            case self::TYPE_ARRAY_SHORT:
                $arrayItems = [];

                foreach ($this->value as $key => $value) {
                    $arrayItems[] = new Node\Expr\ArrayItem(
                        (new ValueGenerator($value))->generate(),
                        (new ValueGenerator($key))->generate()
                    );
                }

                return new Node\Expr\Array_(
                    $arrayItems,
                    ['kind' => Node\Expr\Array_::KIND_SHORT]
                );
            case self::TYPE_OTHER:
                if ($this->value instanceof Node\Expr) {
                    return $this->value;
                }
                // no break
            default:
                throw new Exception\RuntimeException(
                    \sprintf('Type "%s" is unknown or cannot be used as property default value.', \get_class($value))
                );
        }
    }
}
