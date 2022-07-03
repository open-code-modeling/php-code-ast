<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);
namespace OpenCodeModeling\CodeAst\Builder;

trait StrictTrait
{
    private bool $strict = false;

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function setStrict(bool $strict): self
    {
        $this->strict = $strict;

        return $this;
    }
}
