<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);
namespace OpenCodeModeling\CodeAst\Builder;

trait TypedTrait
{
    private bool $typed = true;

    public function setTyped(bool $typed): self
    {
        $this->typed = $typed;

        return $this;
    }

    public function isTyped(): bool
    {
        return $this->typed;
    }
}
