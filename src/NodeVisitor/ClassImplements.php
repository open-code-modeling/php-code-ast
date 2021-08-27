<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

final class ClassImplements extends NodeVisitorAbstract
{
    /**
     * @var string[]
     */
    private array $implements;

    public function __construct(string ...$implements)
    {
        $this->implements = $implements;
    }

    public function afterTraverse(array $nodes): ?array
    {
        $implements = $this->filterImplements($nodes);

        if (\count($implements) === 0) {
            return null;
        }

        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Stmt\Class_) {
                $classImplements = $node->implements;
                foreach ($implements as $import) {
                    $classImplements[] = new Name($import);
                }
                $node->implements = $classImplements;
            }

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        $classImplements = $stmt->implements;
                        foreach ($implements as $import) {
                            $classImplements[] = new Name($import);
                        }
                        $stmt->implements = $classImplements;
                    }
                }
            }
        }

        return $newNodes;
    }

    private function filterImplements(array $nodes): array
    {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        return $this->filterClassImplements($stmt);
                    }
                }
            } elseif ($node instanceof Stmt\Class_) {
                return $this->filterClassImplements($node);
            }
        }

        return $this->implements;
    }

    private function filterClassImplements(Stmt\Class_ $node): array
    {
        $implements = $this->implements;

        foreach ($node->implements as $implementName) {
            $implements = \array_filter($implements, static function (string $implement) use ($implementName) {
                return $implement !== ($implementName instanceof FullyQualified
                        ? '\\' . $implementName->toString()
                        : (string) $implementName);
            });
        }

        return $implements;
    }
}
