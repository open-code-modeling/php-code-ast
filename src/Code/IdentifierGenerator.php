<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use OpenCodeModeling\CodeAst\IdentifiedStatementGenerator;
use PhpParser\Node\Stmt;

final class IdentifierGenerator implements IdentifiedStatementGenerator
{
    /**
     * @var \OpenCodeModeling\CodeAst\StatementGenerator
     */
    private $statementGenerator;

    /**
     * @var string
     */
    private $identifier;

    public function __construct(string $identifier, \OpenCodeModeling\CodeAst\StatementGenerator $statementGenerator)
    {
        $this->identifier = $identifier;
        $this->statementGenerator = $statementGenerator;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function generate(): array
    {
        $stmt = $this->statementGenerator->generate();

        return $stmt instanceof Stmt ? [$stmt] : $stmt;
    }
}
