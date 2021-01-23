<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code\DocBlock\Tag;

/**
 * Code is largely lifted from the Zend\Code\Generator\DocBlock\Tag\MethodTag implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class MethodTag extends AbstractTypeableTag
{
    /**
     * @var string
     */
    protected string $methodName;

    /**
     * @var bool
     */
    protected bool $isStatic = false;

    public function __construct(
        ?string $methodName = null,
        array $types = [],
        ?string $description = null,
        bool $isStatic = false
    ) {
        if (! empty($methodName)) {
            $this->setMethodName($methodName);
        }

        $this->setIsStatic($isStatic);

        parent::__construct($types, $description);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'method';
    }

    /**
     * @param bool $isStatic
     * @return MethodTag
     */
    public function setIsStatic($isStatic): self
    {
        $this->isStatic = $isStatic;

        return $this;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * @param string $methodName
     * @return MethodTag
     */
    public function setMethodName(string $methodName): self
    {
        $this->methodName = \rtrim($methodName, ')(');

        return $this;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        $output = '@method'
            . ($this->isStatic ? ' static' : '')
            . (! empty($this->types) ? ' ' . $this->getTypesAsString() : '')
            . (! empty($this->methodName) ? ' ' . $this->methodName . '()' : '')
            . (! empty($this->description) ? ' ' . $this->description : '');

        return $output;
    }
}
