<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;

class StrictType extends NodeVisitorAbstract
{
    public function afterTraverse(array $nodes)
    {
        if ($this->hasStrictType($nodes)) {
            return null;
        }
        // TODO file comments ?
        \array_unshift($nodes, new Stmt\Declare_([new Stmt\DeclareDeclare('strict_types', new LNumber(1))]));

        return $nodes;
    }

    private function hasStrictType(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Stmt\Declare_
                && \strtolower($node->declares[0]->key->name) === 'strict_types'
            ) {
                return true;
            }
        }

        return false;
    }
}
