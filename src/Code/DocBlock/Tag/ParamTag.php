<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code\DocBlock\Tag;

/**
 * Code is largely lifted from the Zend\Code\Generator\DocBlock\Tag\ParamTag implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class ParamTag extends AbstractTypeableTag
{
    protected string $variableName;

    private bool $variadic = false;

    /**
     * ParamTag constructor.
     * @param string|null $variableName
     * @param string[]|string $types
     * @param string|null $description
     */
    public function __construct(?string $variableName = null, $types = [], ?string $description = null)
    {
        if (! empty($variableName)) {
            $this->setVariableName($variableName);
        }

        parent::__construct($types, $description);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'param';
    }

    /**
     * @param string $variableName
     * @return ParamTag
     */
    public function setVariableName(string $variableName): self
    {
        $this->variableName = \ltrim($variableName, '$');

        return $this;
    }

    /**
     * @return string
     */
    public function getVariableName(): string
    {
        return $this->variableName;
    }

    /**
     * @param bool $variadic
     *
     * @return ParamTag
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
    public function generate(): string
    {
        $output = '@param'
            . (! empty($this->types) ? ' ' . $this->getTypesAsString() : '')
            . ($this->variadic ? ' ...' : ' ')
            . (! empty($this->variableName) ? '$' . $this->variableName : '')
            . (! empty($this->description) ? ' ' . $this->description : '');

        return $output;
    }
}
