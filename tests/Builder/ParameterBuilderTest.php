<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\ParameterBuilder;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ParameterBuilderTest extends TestCase
{
    /**
     * @var Parser
     */
    private Parser $parser;

    /**
     * @var Standard
     */
    private Standard $printer;

    public function setUp(): void
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard(['shortArraySyntax' => true]);
    }

    /**
     * @test
     */
    public function it_generates_property_with_default_value_from_node(): void
    {
        $method = ClassMethodBuilder::fromScratch('setRecordData')
            ->setReturnType('void')
            ->setParameters(
                ParameterBuilder::fromScratch('recordData', 'array')->setDefaultValue(null)
            );
        $classBuilder = ClassBuilder::fromScratch('TestClass');
        $classBuilder->addMethod($method);

        $nodeTraverser = new NodeTraverser();
        $classBuilder->injectVisitors($nodeTraverser, $this->parser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
class TestClass
{
    public function setRecordData(array $recordData = null) : void
    {
    }
}
EOF;
        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));

        $classBuilder = ClassBuilder::fromNodes(...$nodeTraverser->traverse($this->parser->parse('')));
        $nodeTraverser = new NodeTraverser();
        $classBuilder->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }
}
