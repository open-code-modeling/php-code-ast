<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst;

use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassPropertyBuilder;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Builder\InterfaceBuilder;
use OpenCodeModeling\CodeAst\Builder\PhpFile;
use OpenCodeModeling\CodeAst\FileCodeGenerator;
use OpenCodeModeling\CodeAst\Package\ClassInfo;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;
use OpenCodeModeling\Filter\FilterFactory;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class FileCodeGeneratorTest extends TestCase
{
    private Parser $parser;

    private Standard $printer;

    private FileCodeGenerator $fileCodeGenerator;

    protected ClassInfoList $classInfoList;

    public function setUp(): void
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard(['shortArraySyntax' => true]);

        $composerFile = <<<'JSON'
        {
            "autoload": {
                "psr-4": {
                    "MyService\\": "src/"
                }
            },
            "autoload-dev": {
                "psr-4": {
                    "MyServiceTest\\": "tests/"
                }
            }
        }
        JSON;

        $this->classInfoList = new ClassInfoList();

        $this->classInfoList->addClassInfo(
            ...Psr4Info::fromComposer(
            '/service',
            $composerFile,
            FilterFactory::directoryToNamespaceFilter(),
            FilterFactory::namespaceToDirectoryFilter(),
        )
        );

        $this->fileCodeGenerator = new FileCodeGenerator($this->parser, $this->printer, $this->classInfoList);
    }

    /**
     * @test
     */
    public function it_generates_files(): void
    {
        $testClass = ClassBuilder::fromScratch('TestClass', 'MyService')->setFinal(true);
        $myInterface = InterfaceBuilder::fromScratch('MyInterface', 'MyService');

        $fileCollection = FileCollection::fromItems($testClass, $myInterface);

        $files = $this->fileCodeGenerator->generateFiles($fileCollection);

        $expectedTestClass = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        final class TestClass
        {
        }
        EOF;

        $expectedMyInterface = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        interface MyInterface
        {
        }
        EOF;

        $this->assertArrayHasKey('/service/src/MyInterface.php', $files);
        $this->assertArrayHasKey('/service/src/TestClass.php', $files);
        $this->assertSame($expectedTestClass, $files['/service/src/TestClass.php']);
        $this->assertSame($expectedMyInterface, $files['/service/src/MyInterface.php']);
    }

    /**
     * @test
     */
    public function it_generates_files_with_current_ast(): void
    {
        $testClass = ClassBuilder::fromScratch('TestClass', 'MyService')->setFinal(true);
        $myInterface = InterfaceBuilder::fromScratch('MyInterface', 'MyService');

        $expectedTestClass = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        final class TestClass
        {
            public const TEST = true;
            public function test()
            {
                $tmp = 1;
            }
        }
        EOF;

        $expectedMyInterface = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        interface MyInterface
        {
            public function test();
        }
        EOF;

        $currentFileAst = function (PhpFile $classBuilder, ClassInfo $classInfo) use (
            $expectedMyInterface,
            $expectedTestClass
        ) {
            $path = $classInfo->getPath($classBuilder->getNamespace() . '\\' . $classBuilder->getName());
            $filename = $classInfo->getFilenameFromPathAndName($path, $classBuilder->getName());

            switch ($filename) {
                case '/service/src/TestClass.php':
                    $code = $expectedTestClass;
                    break;
                case '/service/src/MyInterface.php':
                    $code = $expectedMyInterface;
                    break;
                default:
                    $code = '';
                    break;
            }

            return $this->parser->parse($code);
        };

        $fileCollection = FileCollection::fromItems($testClass, $myInterface);

        $files = $this->fileCodeGenerator->generateFiles($fileCollection, $currentFileAst);

        $this->assertArrayHasKey('/service/src/MyInterface.php', $files);
        $this->assertArrayHasKey('/service/src/TestClass.php', $files);
        $this->assertSame($expectedTestClass, $files['/service/src/TestClass.php']);
        $this->assertSame($expectedMyInterface, $files['/service/src/MyInterface.php']);
    }

    /**
     * @test
     */
    public function it_add_class_constants_for_properties(): void
    {
        $testClass = ClassBuilder::fromScratch('TestClass', 'MyService')
            ->setFinal(true)
            ->addProperty(
                ClassPropertyBuilder::fromScratch('foo', 'string'),
                ClassPropertyBuilder::fromScratch('bar', 'int'),
            );

        $testClassOther = ClassBuilder::fromScratch('TestClassOther', 'MyService')
            ->setFinal(true)
            ->addProperty(
                ClassPropertyBuilder::fromScratch('foo', 'float'),
                ClassPropertyBuilder::fromScratch('bar', 'bool'),
            );

        $expectedTestClass = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        final class TestClass
        {
            public const FOO = 'foo';
            public const BAR = 'bar';
            private string $foo;
            private int $bar;
        }
        EOF;

        $expectedTestClassOther = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        final class TestClassOther
        {
            public const FOO = 'foo';
            public const BAR = 'bar';
            private float $foo;
            private bool $bar;
        }
        EOF;

        $fileCollection = FileCollection::fromItems($testClass, $testClassOther);

        $this->fileCodeGenerator->addClassConstantsForProperties(
            $fileCollection,
            FilterFactory::constantNameFilter(),
            FilterFactory::constantValueFilter()
        );

        $files = $this->fileCodeGenerator->generateFiles($fileCollection);

        $this->assertArrayHasKey('/service/src/TestClassOther.php', $files);
        $this->assertArrayHasKey('/service/src/TestClass.php', $files);
        $this->assertSame($expectedTestClass, $files['/service/src/TestClass.php']);
        $this->assertSame($expectedTestClassOther, $files['/service/src/TestClassOther.php']);
    }

    /**
     * @test
     */
    public function it_add_getter_methods_for_properties(): void
    {
        $testClass = ClassBuilder::fromScratch('TestClass', 'MyService')
            ->setFinal(true)
            ->addProperty(
                ClassPropertyBuilder::fromScratch('foo', 'string'),
                ClassPropertyBuilder::fromScratch('bar', 'int'),
            );

        $testClassOther = ClassBuilder::fromScratch('TestClassOther', 'MyService')
            ->setFinal(true)
            ->addProperty(
                ClassPropertyBuilder::fromScratch('foo', 'float'),
                ClassPropertyBuilder::fromScratch('bar', 'bool'),
            );

        $expectedTestClass = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        final class TestClass
        {
            private string $foo;
            private int $bar;
            public function foo() : string
            {
                return $this->foo;
            }
            public function bar() : int
            {
                return $this->bar;
            }
        }
        EOF;

        $expectedTestClassOther = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        final class TestClassOther
        {
            private float $foo;
            private bool $bar;
            public function foo() : float
            {
                return $this->foo;
            }
            public function bar() : bool
            {
                return $this->bar;
            }
        }
        EOF;

        $fileCollection = FileCollection::fromItems($testClass, $testClassOther);
        $fileCollection = FileCollection::emptyList()->addFileCollection($fileCollection);

        $this->fileCodeGenerator->addGetterMethodsForProperties(
            $fileCollection,
            true,
            FilterFactory::methodNameFilter()
        );

        $files = $this->fileCodeGenerator->generateFiles($fileCollection);

        $this->assertArrayHasKey('/service/src/TestClassOther.php', $files);
        $this->assertArrayHasKey('/service/src/TestClass.php', $files);
        $this->assertSame($expectedTestClass, $files['/service/src/TestClass.php']);
        $this->assertSame($expectedTestClassOther, $files['/service/src/TestClassOther.php']);
    }

    /**
     * @test
     */
    public function it_add_getter_methods_for_properties_via_visit(): void
    {
        $testClass = ClassBuilder::fromScratch('TestClass', 'MyService')
            ->setFinal(true)
            ->addProperty(
                ClassPropertyBuilder::fromScratch('foo', 'string'),
                ClassPropertyBuilder::fromScratch('bar', 'int'),
            );

        $testClassOther = ClassBuilder::fromScratch('TestClassOther', 'MyService')
            ->setFinal(true)
            ->addProperty(
                ClassPropertyBuilder::fromScratch('foo', 'float'),
                ClassPropertyBuilder::fromScratch('bar', 'bool'),
            );

        $expectedTestClass = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        final class TestClass
        {
            private string $foo;
            private int $bar;
        }
        EOF;

        $expectedTestClassOther = <<<'EOF'
        <?php
        
        declare (strict_types=1);
        namespace MyService;
        
        final class TestClassOther
        {
            private float $foo;
            private bool $bar;
            public function foo() : float
            {
                return $this->foo;
            }
            public function bar() : bool
            {
                return $this->bar;
            }
        }
        EOF;

        $fileCollection = FileCollection::fromItems($testClass, $testClassOther);

        $fileCollection->filter(fn (ClassBuilder $classBuilder) => $classBuilder->getName() === 'TestClassOther')
            ->visit(fn (ClassBuilder $classBuilder) => $this->fileCodeGenerator->addPropertiesGetterMethods($classBuilder, true, FilterFactory::methodNameFilter()));

        $files = $this->fileCodeGenerator->generateFiles($fileCollection);

        $this->assertArrayHasKey('/service/src/TestClassOther.php', $files);
        $this->assertArrayHasKey('/service/src/TestClass.php', $files);
        $this->assertSame($expectedTestClass, $files['/service/src/TestClass.php']);
        $this->assertSame($expectedTestClassOther, $files['/service/src/TestClassOther.php']);
    }
}
