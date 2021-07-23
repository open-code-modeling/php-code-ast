<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use PhpParser\Node\Stmt;

trait FindInsertPositionForType
{
    /**
     * @param Stmt[] $stmts
     * @param string $type
     * @param array $decTypes List of types (FQCN) where position should be decremented
     * @param array $incTypes List of types (FQCN) where position should be incremented
     * @return int
     */
    private function findInsertPositionForType(
        array $stmts,
        string $type,
        array $decTypes = [],
        array $incTypes = []
    ): int {
        $pos = -1;
        $length = 0;

        foreach ($stmts as $key => $stmt) {
            $class = \get_class($stmt);

            if ($stmt instanceof $type) {
                $pos = (int) $key;
            }
            $length++;

            if (true === \in_array($class, $decTypes, true)) {
                $length--;
            }
            if (true === \in_array($class, $incTypes, true)) {
                $length++;
            }
        }

        return $pos === -1 ? $length : ++$pos;
    }
}
