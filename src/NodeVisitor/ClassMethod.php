<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

class ClassMethod extends NodeVisitorAbstract
{
    /**
     * @var MethodGenerator
     **/
    private $methodGenerator;

    public function __construct(MethodGenerator $methodGenerator)
    {
        $this->methodGenerator = $methodGenerator;
    }

    public function afterTraverse(array $nodes): ?array
    {
        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        if ($this->checkMethodExists($stmt)) {
                            return null;
                        }
                        $stmt->stmts[] = $this->methodGenerator->generate();
                    }
                }
            } elseif ($node instanceof Stmt\Class_) {
                if ($this->checkMethodExists($node)) {
                    return null;
                }
                $node->stmts[] = $this->methodGenerator->generate();
            }
        }

        return $newNodes;
    }

    private function checkMethodExists(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod
                && $stmt->name->name === $this->methodGenerator->getName()
            ) {
                return true;
            }
        }

        return false;
    }
}
