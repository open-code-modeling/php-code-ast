<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

final class ClassFile extends NodeVisitorAbstract
{
    /**
     * @var bool
     */
    private bool $classExists = false;

    /**
     * @var ClassGenerator
     **/
    private ClassGenerator $classGenerator;

    public function __construct(ClassGenerator $classGenerator)
    {
        $this->classGenerator = $classGenerator;
    }

    public function beforeTraverse(array $nodes)
    {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Class_) {
                        $this->classExists = $stmt->name->name === $this->classGenerator->getName();
                    }
                }
            } elseif ($node instanceof Class_) {
                $this->classExists = $node->name->name === $this->classGenerator->getName();
            }
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?array
    {
        if ($this->classExists === false) {
            $nodes[] = $this->classGenerator->generate();

            return $nodes;
        }

        return null;
    }
}
