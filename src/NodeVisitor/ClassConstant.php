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
use PhpParser\NodeVisitorAbstract;

final class ClassConstant extends NodeVisitorAbstract
{
    /**
     * @var IdentifierGenerator
     */
    private $lineGenerator;

    public function __construct(IdentifierGenerator $lineGenerator)
    {
        $this->lineGenerator = $lineGenerator;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            if ($definitions = $this->constant($node)) {
                $node->stmts = \array_merge(
                    $definitions,
                    $node->stmts
                );

                return $node;
            }
        }

        return null;
    }

    private function isAlreadyDefined(
        string $lineIdentifier,
        Class_ $node
    ): bool {
        $alreadyDefined = false;

        foreach ($node->stmts as $stmt) {
            if (! $stmt instanceof Node\Stmt\ClassConst) {
                continue;
            }

            if ($lineIdentifier === $stmt->consts[0]->name->name) {
                $alreadyDefined = true;
                break;
            }
        }

        return $alreadyDefined;
    }

    private function constant(Class_ $node): ?array
    {
        $isAlreadyDefined = $this->isAlreadyDefined(
            $this->lineGenerator->getIdentifier(),
            $node
        );

        if ($isAlreadyDefined === false) {
            return $this->lineGenerator->generate();
        }

        return null;
    }
}
