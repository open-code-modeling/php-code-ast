<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use PhpParser\Parser;

final class BodyGenerator implements StatementGenerator
{
    /**
     * @var Parser
     **/
    private Parser $parser;

    /**
     * @var string
     */
    private string $code;

    public function __construct(Parser $parser, string $code)
    {
        $this->parser = $parser;
        $this->code = $code;
    }

    public function generate(): ?array
    {
        return $this->parser->parse('<?php ' . PHP_EOL . $this->code);
    }
}
