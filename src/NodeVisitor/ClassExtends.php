<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Exception\LogicException;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

final class ClassExtends extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    private $extends;

    public function __construct(string $extends)
    {
        $this->extends = $extends;
    }

    public function afterTraverse(array $nodes): ?array
    {
        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        if ($this->checkExtendsExists($stmt)) {
                            return null;
                        }
                        $stmt->extends = new Name($this->extends);
                    }
                }
            } elseif ($node instanceof Stmt\Class_) {
                if ($this->checkExtendsExists($node)) {
                    return null;
                }
                $node->extends = new Name($this->extends);
            }
        }

        return $newNodes;
    }

    private function checkExtendsExists(Stmt\Class_ $node): bool
    {
        $exists = $this->extends === (string) $node->extends;

        if (false === $exists && null !== $node->extends) {
            throw new LogicException(\sprintf(
                'Class "%s" extends already from class "%s". Could not add extends from class "%s"',
                $node->name->name,
                (string) $node->extends,
                $this->extends
            ));
        }

        return $exists;
    }
}
