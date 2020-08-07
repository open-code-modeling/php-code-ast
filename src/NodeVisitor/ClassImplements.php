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

class ClassImplements extends NodeVisitorAbstract
{
    /**
     * @var string[]
     */
    private $implements;

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
        $implements = $this->implements;

        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        foreach ($stmt->implements as $implementName) {
                            $implements = \array_filter($implements, static function (string $implement) use ($implementName) {
                                return $implement !== (string) $implementName;
                            });
                        }
                    }
                }
            }
        }

        return $implements;
    }
}
