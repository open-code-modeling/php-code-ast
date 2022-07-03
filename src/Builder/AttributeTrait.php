<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

trait AttributeTrait
{
    /**
     * @var AttributeBuilder[]
     */
    protected array $attributes = [];

    public function setAttributes(AttributeBuilder ...$attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function addAttribute(AttributeBuilder $attribute): self
    {
        $this->attributes[] = $attribute;

        return $this;
    }

    /**
     * @return AttributeBuilder[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
