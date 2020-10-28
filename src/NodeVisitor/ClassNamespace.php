<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use PhpParser\BuilderFactory;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;

class ClassNamespace extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var BuilderFactory
     **/
    private $builderFactory;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
        $this->builderFactory = new BuilderFactory();
    }

    public static function fromNode(Stmt\Namespace_ $node): self
    {
        return new self($node->name->toString());
    }

    public function afterTraverse(array $nodes): ?array
    {
        if ($this->hasNamespace($nodes)) {
            return null;
        }

        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($this->isNodeStrictType($node)) {
                $newNodes[] = $this->builderFactory->namespace($this->namespace)->getNode();
            }
        }

        return $newNodes;
    }

    private function isNodeStrictType(Stmt $node): bool
    {
        return $node instanceof Stmt\Declare_
            && \strtolower($node->declares[0]->key->name) === 'strict_types';
    }

    private function hasNamespace(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Stmt\Namespace_) {
                return true;
            }
        }

        return false;
    }
}
