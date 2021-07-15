<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

final class ClassConstant extends NodeVisitorAbstract
{
    use FindInsertPositionForType;

    /**
     * @var IdentifierGenerator
     */
    private IdentifierGenerator $lineGenerator;

    public function __construct(IdentifierGenerator $lineGenerator)
    {
        $this->lineGenerator = $lineGenerator;
    }

    public function afterTraverse(array $nodes): ?array
    {
        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Class_ || $stmt instanceof Node\Stmt\Interface_) {
                        if ($this->checkConstantExists($stmt)) {
                            return null;
                        }
                        \array_splice(
                            $stmt->stmts,
                            $this->findInsertPositionForType($stmt->stmts, Node\Stmt\ClassConst::class),
                            0,
                            $this->lineGenerator->generate()
                        );
                    }
                }
            } elseif ($node instanceof Class_ || $node instanceof Node\Stmt\Interface_) {
                if ($this->checkConstantExists($node)) {
                    return null;
                }
                \array_splice(
                    $node->stmts,
                    $this->findInsertPositionForType($node->stmts, Node\Stmt\ClassConst::class),
                    0,
                    $this->lineGenerator->generate()
                );
            }
        }

        return $newNodes;
    }

    /**
     * @param Class_|Node\Stmt\Interface_ $node
     */
    private function checkConstantExists($node): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassConst
                && $stmt->consts[0]->name->name === $this->lineGenerator->getIdentifier()
            ) {
                return true;
            }
        }

        return false;
    }
}
