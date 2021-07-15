<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassPropertyBuilder;
use OpenCodeModeling\CodeAst\Code\PropertyGenerator;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ClassPropertyBuilderTest extends TestCase
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
    public function it_generates_property_for_empty_class(): void
    {
        $ast = $this->parser->parse('');

        $classFactory = ClassBuilder::fromScratch('TestClass', 'My\\Awesome\\Service');
        $classFactory->setProperties(ClassPropertyBuilder::fromScratch('aggregateId', 'string', false));

        $this->assertTrue($classFactory->hasProperty('aggregateId'));

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    /**
     * @var string
     */
    private $aggregateId;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($ast)));
    }

    /**
     * @test
     */
    public function it_generates_property_for_empty_class_from_template(): void
    {
        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    private string $aggregateId;
    protected ?string $name;
}
EOF;

        $ast = $this->parser->parse($expected);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $properties = $classFactory->getProperties();

        $this->assertCount(2, $properties);

        $this->assertSame('aggregateId', $properties['aggregateId']->getName());
        $this->assertSame('string', $properties['aggregateId']->getType());
        $this->assertTrue($properties['aggregateId']->isPrivate());

        $this->assertSame('name', $properties['name']->getName());
        $this->assertSame('?string', $properties['name']->getType());
        $this->assertTrue($properties['name']->isProtected());

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_sort_of_class_properties(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    private $b;
    private $a;
    private $d;
    private $c;
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);

        $classFactory->sortProperties(function (ClassPropertyBuilder $a, ClassPropertyBuilder $b) {
            return $a->getName() <=> $b->getName();
        });

        $properties = \array_values($classFactory->getProperties());
        $this->assertCount(4, $properties);
        $this->assertSame('a', $properties[0]->getName());
        $this->assertSame('b', $properties[1]->getName());
        $this->assertSame('c', $properties[2]->getName());
        $this->assertSame('d', $properties[3]->getName());

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    private $a;
    private $b;
    private $c;
    private $d;
}
EOF;

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_supports_adding_of_class_properties(): void
    {
        $code = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public const TEST = 1;
    private $a;
    public function test(): void
    {
    }
}
EOF;

        $ast = $this->parser->parse($code);

        $classFactory = ClassBuilder::fromNodes(...$ast);
        $classFactory->addProperty(ClassPropertyBuilder::fromScratch('a', 'string'));
        $classFactory->addProperty(ClassPropertyBuilder::fromScratch('b', 'string'));
        $classFactory->addProperty(ClassPropertyBuilder::fromScratch('c', 'string'));
        $classFactory->addProperty(ClassPropertyBuilder::fromScratch('d', 'string'));

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
namespace My\Awesome\Service;

class TestClass
{
    public const TEST = 1;
    private string $a;
    private string $b;
    private string $c;
    private string $d;
    public function test() : void
    {
    }
}
EOF;

        $nodeTraverser = new NodeTraverser();
        $classFactory->injectVisitors($nodeTraverser, $this->parser);

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }

    /**
     * @test
     */
    public function it_generates_property_with_default_value_from_node(): void
    {
        $classBuilder = ClassBuilder::fromScratch('TestClass');
        $classBuilder->addProperty(
            ClassPropertyBuilder::fromNode(
                (new PropertyGenerator('recordData', 'array'))->setDefaultValue(null)->generate()
            )
        );

        $nodeTraverser = new NodeTraverser();
        $classBuilder->injectVisitors($nodeTraverser, $this->parser);

        $expected = <<<'EOF'
<?php

declare (strict_types=1);
class TestClass
{
    private array $recordData = null;
}
EOF;

        $this->assertSame($expected, $this->printer->prettyPrintFile($nodeTraverser->traverse($this->parser->parse(''))));
    }
}
