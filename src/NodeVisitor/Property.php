<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

final class Property extends NodeVisitorAbstract
{
    /**
     * @var PropertyGenerator
     **/
    private $propertyGenerator;

    public function __construct(PropertyGenerator $propertyGenerator)
    {
        $this->propertyGenerator = $propertyGenerator;
    }

    public static function forClassProperty(
        string $name = null,
        string $type = null,
        $defaultValue = null,
        bool $typed = false,
        int $flags = PropertyGenerator::FLAG_PRIVATE
    ): self {
        return new self(
            new PropertyGenerator($name, $type, $defaultValue, $typed, $flags)
        );
    }

    public function afterTraverse(array $nodes): ?array
    {
        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Class_) {
                        if ($this->checkPropertyExists($stmt)) {
                            return null;
                        }
                        $stmt->stmts[] = $this->propertyGenerator->generate();
                    }
                }
            } elseif ($node instanceof Stmt\Class_) {
                if ($this->checkPropertyExists($node)) {
                    return null;
                }
                $node->stmts[] = $this->propertyGenerator->generate();
            }
        }

        return $newNodes;
    }

    private function checkPropertyExists(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Property
                && $stmt->props[0]->name->name === $this->propertyGenerator->getName()
            ) {
                return true;
            }
        }

        return false;
    }
}
