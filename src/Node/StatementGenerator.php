<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Node;

use OpenCodeModeling\CodeAst\IdentifiedStatementGenerator;
use PhpParser\Node;

final class StatementGenerator implements IdentifiedStatementGenerator
{
    /**
     * @var string
     **/
    private $identifier;

    /**
     * @var array<Node\Stmt>
     **/
    private $stmts;

    /**
     * @param string $identifier
     * @param Node\Stmt ...$stmts
     */
    public function __construct(string $identifier, Node\Stmt ...$stmts)
    {
        $this->identifier = $identifier;
        $this->stmts = $stmts;
    }

    /**
     * @return string
     */
    public function identifier(): string
    {
        return $this->identifier;
    }

    public function generate()
    {
        return $this->stmts;
    }
}
