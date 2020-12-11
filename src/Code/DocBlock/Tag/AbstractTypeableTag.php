<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code\DocBlock\Tag;

/**
 * Code is largely lifted from the Zend\Code\Generator\DocBlock\Tag\AbstractTypeableTag implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 *
 * This abstract class can be used as parent for all tags
 * that use a type part in their content.
 *
 * @see http://www.phpdoc.org/docs/latest/for-users/phpdoc/types.html
 * @internal
 */
abstract class AbstractTypeableTag implements Tag
{
    /**
     * @var string
     */
    protected ?string $description = null;

    /**
     * @var array
     */
    protected array $types = [];

    /**
     * @param string|string[] $types
     * @param string $description
     */
    public function __construct($types = [], ?string $description = null)
    {
        if (! empty($types)) {
            $this->setTypes($types);
        }

        if (! empty($description)) {
            $this->setDescription($description);
        }
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Array of types or string with types delimited by pipe (|)
     * e.g. array('int', 'null') or "int|null"
     *
     * @param array|string $types
     * @return AbstractTypeableTag
     */
    public function setTypes($types): self
    {
        if (\is_string($types)) {
            $types = \explode('|', $types);
        }
        $this->types = $types;

        return $this;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param string $delimiter
     * @return string
     */
    public function getTypesAsString($delimiter = '|'): string
    {
        return \implode($delimiter, $this->types);
    }
}
