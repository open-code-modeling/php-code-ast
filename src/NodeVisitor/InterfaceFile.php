<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\InterfaceGenerator;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

final class InterfaceFile extends NodeVisitorAbstract
{
    /**
     * @var bool
     */
    private bool $classExists = false;

    /**
     * @var InterfaceGenerator
     **/
    private \OpenCodeModeling\CodeAst\Code\InterfaceGenerator $interfaceGenerator;

    public function __construct(InterfaceGenerator $interfaceGenerator)
    {
        $this->interfaceGenerator = $interfaceGenerator;
    }

    public function beforeTraverse(array $nodes)
    {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Interface_) {
                        $this->classExists = $stmt->name->name === $this->interfaceGenerator->getName();
                    }
                }
            } elseif ($node instanceof Interface_) {
                $this->classExists = $node->name->name === $this->interfaceGenerator->getName();
            }
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?array
    {
        if ($this->classExists === false) {
            $nodes[] = $this->interfaceGenerator->generate();

            return $nodes;
        }

        return null;
    }
}
