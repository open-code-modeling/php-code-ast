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
     * @return int
     */
    private function findInsertPositionForType(array $stmts, string $type): int
    {
        $pos = 0;
        $length = 0;

        foreach ($stmts as $key => $stmt) {
            if ($stmt instanceof $type) {
                $pos = (int) $key;
            }
            $length++;
        }

        return $pos === 0 ? ++$length : ++$pos;
    }
}
