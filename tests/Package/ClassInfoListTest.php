<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Package;

use OpenCodeModeling\CodeAst\Exception\RuntimeException;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use OpenCodeModeling\CodeAst\Package\Psr4Info;
use OpenCodeModeling\Filter\FilterFactory;
use PHPUnit\Framework\TestCase;

final class ClassInfoListTest extends TestCase
{
    /**
     * @var ClassInfoList
     */
    private ClassInfoList $classInfoList;

    public function setUp(): void
    {
        $this->classInfoList = new ClassInfoList(
            new Psr4Info(
                'tmp/',
                'Acme',
                FilterFactory::directoryToNamespaceFilter(),
                FilterFactory::namespaceToDirectoryFilter(),
            ),
            new Psr4Info(
                'src/',
                'MyApp',
                FilterFactory::directoryToNamespaceFilter(),
                FilterFactory::namespaceToDirectoryFilter(),
            ),
            new Psr4Info(
                'lib/',
                'MyLibrary',
                FilterFactory::directoryToNamespaceFilter(),
                FilterFactory::namespaceToDirectoryFilter(),
            ),
        );
    }

    /**
     * @test
     */
    public function it_returns_class_info_for_path(): void
    {
        $classInfo = $this->classInfoList->classInfoForPath('tmp/');
        $this->assertSame('tmp', $classInfo->getSourceFolder());

        $classInfo = $this->classInfoList->classInfoForPath('src/');
        $this->assertSame('src', $classInfo->getSourceFolder());

        $classInfo = $this->classInfoList->classInfoForPath('lib/');
        $this->assertSame('lib', $classInfo->getSourceFolder());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_class_info_for_path_was_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->classInfoList->classInfoForPath('unknown/');
    }

    /**
     * @test
     */
    public function it_returns_class_info_for_filename(): void
    {
        $classInfo = $this->classInfoList->classInfoForFilename('tmp/Order.php');
        $this->assertSame('tmp', $classInfo->getSourceFolder());

        $classInfo = $this->classInfoList->classInfoForFilename('src/Order.php');
        $this->assertSame('src', $classInfo->getSourceFolder());

        $classInfo = $this->classInfoList->classInfoForFilename('lib/Order.php');
        $this->assertSame('lib', $classInfo->getSourceFolder());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_class_info_for_filename_was_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->classInfoList->classInfoForFilename('Unknown/Order.php');
    }

    /**
     * @test
     */
    public function it_returns_class_info_for_namespace(): void
    {
        $classInfo = $this->classInfoList->classInfoForNamespace('Acme');
        $this->assertSame('tmp', $classInfo->getSourceFolder());
        $classInfo = $this->classInfoList->classInfoForNamespace('Acme\\Service\\OrderService');
        $this->assertSame('tmp', $classInfo->getSourceFolder());

        $classInfo = $this->classInfoList->classInfoForNamespace('MyApp');
        $this->assertSame('src', $classInfo->getSourceFolder());
        $classInfo = $this->classInfoList->classInfoForNamespace('MyApp\\Service\\OrderService');
        $this->assertSame('src', $classInfo->getSourceFolder());

        $classInfo = $this->classInfoList->classInfoForNamespace('MyLibrary');
        $this->assertSame('lib', $classInfo->getSourceFolder());
        $classInfo = $this->classInfoList->classInfoForNamespace('MyLibrary\\Service\\OrderService');
        $this->assertSame('lib', $classInfo->getSourceFolder());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_class_info_for_namespace_was_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->classInfoList->classInfoForNamespace('Unknown');
    }
}
