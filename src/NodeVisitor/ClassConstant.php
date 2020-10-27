<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Code\IdentifierGenerator;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
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

    public static function forClassConstant(
        string $constantName,
        string $constantValue,
        int $flags = ClassConstGenerator::FLAG_PUBLIC
    ): ClassConstant {
        return new self(
            new IdentifierGenerator(
                $constantName,
                new ClassConstGenerator($constantName, $constantValue, $flags)
            )
        );
    }

    public function afterTraverse(array $nodes): ?array
    {
        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Class_) {
                        if ($this->checkConstantExists($stmt)) {
                            return null;
                        }
                        $stmt->stmts = \array_merge(
                            $this->lineGenerator->generate(),
                            $stmt->stmts
                        );
                    }
                }
            } elseif ($node instanceof Class_) {
                if ($this->checkConstantExists($node)) {
                    return null;
                }
                $node->stmts = \array_merge(
                    $this->lineGenerator->generate(),
                    $node->stmts
                );
            }
        }

        return $newNodes;
    }

    private function checkConstantExists(Class_ $node): bool
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
