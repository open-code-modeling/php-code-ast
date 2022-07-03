<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Parser;

final class AttributeGenerator implements StatementGenerator
{
    /**
     * @var Parser
     **/
    private Parser $parser;

    /**
     * @var string
     */
    private string $name;
    private array $args;

    public function __construct(Parser $parser, string $name, ...$args)
    {
        $this->parser = $parser;
        $this->name = $name;
        $this->args = $args;
    }

    public function generate(): Node\Attribute
    {
        $args = [];

        foreach ($this->args as $arg) {
            $nodes = $this->parser->parse('<?php ' . PHP_EOL . $arg . ';');

            if (\count($nodes) === 1 && $nodes[0] instanceof Expression) {
                $args[] = new Node\Arg($nodes[0]->expr);
            }
            if (\count($nodes) === 2
                && $nodes[0] instanceof Node\Stmt\Label
                && $nodes[1] instanceof Expression
            ) {
                $args[] = new Node\Arg($nodes[1]->expr, false, false, [], $nodes[0]->name);
            }
        }

        return new Node\Attribute(new Node\Name($this->getName()), $args);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
