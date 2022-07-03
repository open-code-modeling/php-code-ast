<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);
namespace OpenCodeModeling\CodeAst\Builder;

trait FinalTrait
{
    private bool $final = false;

    public function setFinal(bool $final): self
    {
        $this->final = $final;

        return $this;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }
}
