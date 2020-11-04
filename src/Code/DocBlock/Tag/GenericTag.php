<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code\DocBlock\Tag;

/**
 * Code is largely lifted from the Zend\Code\Generator\DocBlock\Tag\GenericTag implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class GenericTag implements Tag
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $content;

    public function __construct(?string $name = null, ?string $content = null)
    {
        if (! empty($name)) {
            $this->setName($name);
        }

        if (! empty($content)) {
            $this->setContent($content);
        }
    }

    /**
     * @param string $name
     * @return GenericTag
     */
    public function setName($name): self
    {
        $this->name = \ltrim($name, '@');

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
     * @param string $content
     * @return GenericTag
     */
    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        $output = '@' . $this->name
            . (! empty($this->content) ? ' ' . $this->content : '');

        return $output;
    }
}
