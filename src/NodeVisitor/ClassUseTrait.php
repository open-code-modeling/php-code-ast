<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

final class ClassUseTrait extends NodeVisitorAbstract
{
    /**
     * @var string[]
     */
    private $traits;

    public function __construct(string ...$traits)
    {
        $this->traits = $traits;
    }

    public function afterTraverse(array $nodes): ?array
    {
        $traits = $this->filterTraits($nodes);

        if (\count($traits) === 0) {
            return null;
        }

        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Stmt\Class_) {
                foreach ($traits as $trait) {
                    \array_unshift($node->stmts, new Stmt\TraitUse([new Name($trait)]));
                }
            }

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        foreach ($traits as $trait) {
                            \array_unshift($stmt->stmts, new Stmt\TraitUse([new Name($trait)]));
                        }
                    }
                }
            }
        }

        return $newNodes;
    }

    private function filterTraits(array $nodes): array
    {
        $useTraits = $this->traits;

        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        foreach ($stmt->stmts as $classStmt) {
                            if ($classStmt instanceof Stmt\TraitUse) {
                                foreach ($classStmt->traits as $trait) {
                                    $useTraits = \array_filter($useTraits,
                                        static function (string $implement) use ($trait) {
                                            return $implement !== (string) $trait;
                                        });
                                }
                            }
                        }
                    }
                }
            }
        }

        return $useTraits;
    }
}
