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

class ClassFile extends NodeVisitorAbstract
{
    /**
     * @var bool
     */
    private $classExists = false;

    /**
     * @var ClassGenerator
     **/
    private $classGenerator;

    public function __construct(ClassGenerator $classGenerator)
    {
        $this->classGenerator = $classGenerator;
    }

    public static function fromNode(Class_ $node): self
    {
        return new self(new ClassGenerator($node->name->name));
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
