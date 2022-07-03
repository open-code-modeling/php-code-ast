<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);
namespace OpenCodeModeling\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Code\AbstractMemberGenerator;

trait VisibilityTrait
{
    private int $visibility = 0;

    public function setPrivate(): self
    {
        $this->visibility = AbstractMemberGenerator::FLAG_PRIVATE;

        return $this;
    }

    public function setProtected(): self
    {
        $this->visibility = AbstractMemberGenerator::FLAG_PROTECTED;

        return $this;
    }

    public function setPublic(): self
    {
        $this->visibility = AbstractMemberGenerator::FLAG_PUBLIC;

        return $this;
    }

    public function isPrivate(): bool
    {
        return (bool) ($this->visibility & AbstractMemberGenerator::FLAG_PRIVATE);
    }

    public function isProtected(): bool
    {
        return (bool) ($this->visibility & AbstractMemberGenerator::FLAG_PROTECTED);
    }

    public function isPublic(): bool
    {
        return (bool) ($this->visibility & AbstractMemberGenerator::FLAG_PUBLIC);
    }
}
