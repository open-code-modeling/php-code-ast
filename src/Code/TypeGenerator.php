<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use OpenCodeModeling\CodeAst\Exception\InvalidArgumentException;
use PhpParser\Node;
use PhpParser\NodeAbstract;

/**
 * Code is largely lifted from the Zend\Code\Generator\TypeGenerator implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class TypeGenerator
{
    /**
     * @var bool
     */
    private $isInternalPhpType;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $nullable;

    /**
     * @var string[]
     *
     * @link http://php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration
     */
    private static $internalPhpTypes = [
        'void',
        'int',
        'float',
        'string',
        'bool',
        'array',
        'callable',
        'iterable',
        'object',
    ];

    /**
     * @var string a regex pattern to match valid class names or types
     */
    private static $validIdentifierMatcher = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*'
    . '(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*$/';

    /**
     * @param string $type
     *
     * @return TypeGenerator
     *
     * @throws InvalidArgumentException
     */
    public static function fromTypeString(string $type): TypeGenerator
    {
        [$nullable, $trimmedNullable] = self::trimNullable($type);
        [$wasTrimmed, $trimmedType] = self::trimType($trimmedNullable);

        if (! \preg_match(self::$validIdentifierMatcher, $trimmedType)) {
            throw new InvalidArgumentException(\sprintf(
                'Provided type "%s" is invalid: must conform "%s"',
                $type,
                self::$validIdentifierMatcher
            ));
        }

        $isInternalPhpType = self::isInternalPhpType($trimmedType);

        if ($wasTrimmed && $isInternalPhpType) {
            throw new InvalidArgumentException(\sprintf(
                'Provided type "%s" is an internal PHP type, but was provided with a namespace separator prefix',
                $type
            ));
        }

        if ($nullable && $isInternalPhpType && 'void' === \strtolower($trimmedType)) {
            throw new InvalidArgumentException(\sprintf('Provided type "%s" cannot be nullable', $type));
        }

        $instance = new self();

        $instance->type = $isInternalPhpType ? $trimmedType : $trimmedNullable;
        $instance->nullable = $nullable;
        $instance->isInternalPhpType = $isInternalPhpType;

        return $instance;
    }

    private function __construct()
    {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Normally only one type. Additionally the type null if nullable.
     *
     * @return string[]
     */
    public function types(): array
    {
        $types = [$this->type];

        if ($this->nullable) {
            $types[] = 'null';
        }

        return $types;
    }

    public function generate(): NodeAbstract
    {
        $type = $this->isInternalPhpType
            ? new Node\Identifier(\strtolower($this->type))
            : new Node\Name($this->type);

        return $this->nullable ? new Node\NullableType($type) : $type;
    }

    /**
     * @param string $type
     *
     * @return bool[]|string[] ordered tuple, first key represents whether the type is nullable, second is the
     *                         trimmed string
     */
    private static function trimNullable(string $type): array
    {
        if (0 === \strpos($type, '?')) {
            return [true, \substr($type, 1)];
        }

        return [false, $type];
    }

    /**
     * @param string $type
     *
     * @return bool[]|string[] ordered tuple, first key represents whether the values was trimmed, second is the
     *                         trimmed string
     */
    private static function trimType(string $type): array
    {
        if (0 === \strpos($type, '\\')) {
            return [true, \substr($type, 1)];
        }

        return [false, $type];
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private static function isInternalPhpType(string $type): bool
    {
        return \in_array(\strtolower($type), self::$internalPhpTypes, true);
    }
}
