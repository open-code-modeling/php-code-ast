<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);
namespace OpenCodeModeling\CodeAst\Code;

trait AttributeTrait
{
    /**
     * @var AttributeGenerator[]
     */
    protected array $attributes = [];

    public function setAttributes(AttributeGenerator ...$attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function addAttribute(AttributeGenerator $attribute): self
    {
        $this->attributes[] = $attribute;

        return $this;
    }
}
