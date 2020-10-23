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
    public static function fromTypeString($type): TypeGenerator
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

        $instance->type = $trimmedType;
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

    public function generate(): NodeAbstract
    {
        $nullable = $this->nullable ? '?' : '';

        // TODO nullable

        if ($this->isInternalPhpType) {
            return new Node\Identifier(\strtolower($this->type));
//            return $nullable . strtolower($this->type);
        }

        return new Node\Name($this->type);
//        return $nullable . '\\' . $this->type;
    }

    /**
     * @return string the cleaned type string
     */
    public function __toString(): string
    {
        return \ltrim($this->generate(), '?\\');
    }

    /**
     * @param string $type
     *
     * @return bool[]|string[] ordered tuple, first key represents whether the type is nullable, second is the
     *                         trimmed string
     */
    private static function trimNullable($type): array
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
    private static function trimType($type): array
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
    private static function isInternalPhpType($type): bool
    {
        return \in_array(\strtolower($type), self::$internalPhpTypes, true);
    }
}
