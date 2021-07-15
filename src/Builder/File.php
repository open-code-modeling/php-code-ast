<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;

interface File
{
    public function getName(): ?string;

    /**
     * @param Parser $parser
     * @return NodeVisitor[]
     */
    public function generate(Parser $parser): array;

    public function injectVisitors(NodeTraverser $nodeTraverser, Parser $parser): void;
}
