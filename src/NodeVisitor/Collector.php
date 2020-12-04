<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class Collector extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $visitors = [];

    public function afterTraverse(array $nodes): ?array
    {
        $this->visitors = [];

        foreach ($nodes as $node) {
            $this->determineVisitor($node);
        }

        return null;
    }

    private function determineVisitor(Node $node): void
    {
        switch (true) {
            case $node instanceof Namespace_:
                $this->visitors[] = ClassNamespace::fromNode($node);
                break;
            case $node instanceof Node\Stmt\Class_:
                $this->visitors[] = ClassFile::fromNode($node);

                foreach ($node->stmts as $stmt) {
                    $this->determineVisitor($stmt);
                }
                break;
            case $node instanceof Node\Stmt\ClassConst:
                $this->visitors[] = ClassConstant::fromNode($node);
                break;
            default:
                break;
        }
    }

    public function visitors(): array
    {
        return $this->visitors;
    }

    public function injectVisitors(NodeTraverser $nodeTraverser): void
    {
        foreach ($this->visitors as $visitor) {
            $nodeTraverser->addVisitor(clone $visitor);
        }
    }
}
