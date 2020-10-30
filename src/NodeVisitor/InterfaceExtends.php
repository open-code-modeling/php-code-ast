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

final class InterfaceExtends extends NodeVisitorAbstract
{
    /**
     * @var string[]
     */
    private $extends;

    public function __construct(string ...$extends)
    {
        $this->extends = $extends;
    }

    public function afterTraverse(array $nodes): ?array
    {
        $extends = $this->filterExtends($nodes);

        if (\count($extends) === 0) {
            return null;
        }

        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Stmt\Interface_) {
                $classExtends = $node->extends;
                foreach ($extends as $import) {
                    $classExtends[] = new Name($import);
                }
                $node->extends = $classExtends;
            }

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Interface_) {
                        $classExtends = $stmt->extends;
                        foreach ($extends as $import) {
                            $classExtends[] = new Name($import);
                        }
                        $stmt->extends = $classExtends;
                    }
                }
            }
        }

        return $newNodes;
    }

    private function filterExtends(array $nodes): array
    {
        $extends = $this->extends;

        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Interface_) {
                        foreach ($stmt->extends as $extendName) {
                            $extends = \array_filter($extends, static function (string $extend) use ($extendName) {
                                return $extend !== (string) $extendName;
                            });
                        }
                    }
                }
            }
        }

        return $extends;
    }
}
