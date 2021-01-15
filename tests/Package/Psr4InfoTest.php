<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModelingTest\CodeAst\Package;

use Laminas\Filter\FilterChain;
use Laminas\Filter\Word\SeparatorToSeparator;
use OpenCodeModeling\CodeAst\Package\Psr4Info;
use PHPUnit\Framework\TestCase;

final class Psr4InfoTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_from_composer(): void
    {
        $psr4InfoList = Psr4Info::fromComposer(
            '/service',
            \file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'composer.json'),
            $this->filterDirectoryToNamespace(),
            $this->filterNamespaceToDirectory()
        );

        $this->assertCount(4, $psr4InfoList);

        $this->assertSame('OpenCodeModeling\\CodeAst', $psr4InfoList[0]->getPackagePrefix());
        $this->assertSame('/service/src', $psr4InfoList[0]->getSourceFolder());

        $this->assertSame('Monolog', $psr4InfoList[1]->getPackagePrefix());
        $this->assertSame('/service/src', $psr4InfoList[1]->getSourceFolder());

        $this->assertSame('Monolog', $psr4InfoList[2]->getPackagePrefix());
        $this->assertSame('/service/lib', $psr4InfoList[2]->getSourceFolder());

        $this->assertSame('OpenCodeModelingTest\\CodeAst', $psr4InfoList[3]->getPackagePrefix());
        $this->assertSame('/service/tests', $psr4InfoList[3]->getSourceFolder());

        $this->assertSame(
            '/service/src/Domain/Model/Building/Building.php',
            $psr4InfoList[0]->getFilenameFromPathAndName('/service/src/Domain/Model/Building', 'Building')
        );
        $this->assertSame(
            '/service/src/Domain/Model/Building/Building.php',
            $psr4InfoList[0]->getFilenameFromPathAndName('Domain/Model/Building', 'Building')
        );
    }

    /**
     * @test
     * @dataProvider providerForGetClassNamespace
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::__construct
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::getClassNamespaceFromPath
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::normalizeNamespace
     */
    public function it_returns_class_namespace_from_path($expected, $sourceFolder, $packagePrefix, $path): void
    {
        $psr4Info = new Psr4Info(
            $sourceFolder,
            $packagePrefix,
            $this->filterDirectoryToNamespace(),
            $this->filterNamespaceToDirectory()
        );

        self::assertSame($expected, $psr4Info->getClassNamespaceFromPath($path));
    }

    /**
     * Values are expected, sourceFolder, packagePrefix and path
     *
     * @return array
     */
    public function providerForGetClassNamespace(): array
    {
        return [
            [
                'MyVendor\MyPackage\ModelPath\UserPath',
                'src',
                '\MyVendor\MyPackage\\',
                'ModelPath/UserPath',
            ],
            [
                'MyVendor\MyPackage\ModelPath\UserPath',
                'src',
                '\MyVendor\MyPackage\\',
                'src/ModelPath/UserPath',
            ],
            [
                'MyVendor\MyPackage\ModelPath\UserPath',
                'src',
                '\\MyVendor\\MyPackage\\',
                '/ModelPath/UserPath/',
            ],
            [
                'vendor\package\model\user',
                'src',
                'vendor\package',
                'model/user/',
            ],
            [
                'vendor\package',
                'src',
                'vendor\package',
                '',
            ],
            [
                'vendor',
                'src',
                'vendor',
                '',
            ],
            [
                '',
                'src',
                '',
                '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGetPath
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::__construct
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::getPath
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::normalizeNamespace
     */
    public function it_returns_path_from_namespace($expected, $sourceFolder, $packagePrefix, $fcqn): void
    {
        $psr4Info = new Psr4Info(
            $sourceFolder,
            $packagePrefix,
            $this->filterDirectoryToNamespace(),
            $this->filterNamespaceToDirectory()
        );

        self::assertSame($expected, $psr4Info->getPath($fcqn));
    }

    /**
     * Values are expected, sourceFolder, packagePrefix and fcqn
     *
     * @return array
     */
    public function providerForGetPath(): array
    {
        return [
            [
                'ModelPath/UserPath',
                'src',
                '\MyVendor\MyPackage\\',
                '\MyVendor\MyPackage\ModelPath\UserPath\User',
            ],
            [
                'ModelPath/UserPath',
                'src',
                '\\MyVendor\\MyPackage\\',
                '\\MyVendor\\MyPackage\\ModelPath\\UserPath\\User',
            ],
            [
                '',
                'src',
                'MyVendor\MyPackage',
                'MyVendor\MyPackage\User',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGetFilename
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::__construct
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::getFilenameFromPathAndName
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::normalizePath
     */
    public function it_returns_filename($expected, $sourceFolder, $packagePrefix, $path, $name): void
    {
        $psr4Info = new Psr4Info(
            $sourceFolder,
            $packagePrefix,
            $this->filterDirectoryToNamespace(),
            $this->filterNamespaceToDirectory()
        );

        self::assertSame($expected, $psr4Info->getFilenameFromPathAndName($path, $name));
    }

    /**
     * Values are expected, sourceFolder, packagePrefix, path and name
     *
     * @return array
     */
    public function providerForGetFilename(): array
    {
        return [
            [
                'src/ModelPath/UserPath/User.php',
                'src',
                '\MyVendor\MyPackage\\',
                'ModelPath/UserPath',
                'User',
            ],
            [
                'src/ModelPath/UserPath/User.php',
                'src',
                '\MyVendor\MyPackage\\',
                'src/ModelPath/UserPath',
                'User',
            ],
            [
                'src/ModelPath/UserPath/User.php',
                'src',
                '\\MyVendor\\MyPackage\\',
                'ModelPath/UserPath/',
                'User',
            ],
            [
                'src/model/user.php',
                'src',
                'vendor\package',
                '/model/',
                'user',
            ],
            [
                '/src/User.php',
                '/src/',
                'vendor\package',
                '',
                'User',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGetClassName
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::getClassName
     */
    public function it_returns_class_name($expected, $sourceFolder, $packagePrefix, $fqcn): void
    {
        $psr4Info = new Psr4Info(
            $sourceFolder,
            $packagePrefix,
            $this->filterDirectoryToNamespace(),
            $this->filterNamespaceToDirectory()
        );

        self::assertSame($expected, $psr4Info->getClassName($fqcn));
    }

    /**
     * Values are expected, sourceFolder, packagePrefix and FQCN
     *
     * @return array
     */
    public function providerForGetClassName(): array
    {
        return [
            [
                'User',
                'src',
                '\MyVendor\MyPackage\\',
                '\MyVendor\MyPackage\ModelPath\UserPath\User',
            ],
            [
                'User',
                'src',
                '\\MyVendor\\MyPackage\\',
                '\\MyVendor\\MyPackage\\ModelPath\\UserPath\\User',
            ],
            [
                'User',
                'src',
                '',
                'MyVendor\MyPackage\User',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGetPathAndNameFromFilename
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::getClassName
     */
    public function it_returns_path_and_name_from_filename($expected, $sourceFolder, $packagePrefix, $filename): void
    {
        $psr4Info = new Psr4Info(
            $sourceFolder,
            $packagePrefix,
            $this->filterDirectoryToNamespace(),
            $this->filterNamespaceToDirectory()
        );

        self::assertSame($expected, $psr4Info->getPathAndNameFromFilename($filename));
    }

    /**
     * Values are expected, sourceFolder, packagePrefix and FQCN
     *
     * @return array
     */
    public function providerForGetPathAndNameFromFilename(): array
    {
        return [
            [
                ['ModelPath/UserPath', 'User'],
                'src',
                '\MyVendor\MyPackage\\',
                'src/ModelPath/UserPath/User.php',
            ],
            [
                ['ModelPath/UserPath', 'User'],
                'src',
                '\MyVendor\MyPackage\\',
                'src/ModelPath/UserPath/User',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGetFullyQualifiedClassNameFromFilename
     * @covers       \OpenCodeModeling\CodeAst\Package\Psr4Info::getFullyQualifiedClassNameFromFilename
     */
    public function it_returns_fqcn_from_filename($expected, $sourceFolder, $packagePrefix, $filename): void
    {
        $psr4Info = new Psr4Info(
            $sourceFolder,
            $packagePrefix,
            $this->filterDirectoryToNamespace(),
            $this->filterNamespaceToDirectory()
        );

        self::assertSame($expected, $psr4Info->getFullyQualifiedClassNameFromFilename($filename));
    }

    /**
     * Values are expected, sourceFolder, packagePrefix and FQCN
     *
     * @return array
     */
    public function providerForGetFullyQualifiedClassNameFromFilename(): array
    {
        return [
            [
                'MyVendor\MyPackage\ModelPath\UserPath\User',
                'src',
                '\MyVendor\MyPackage\\',
                'src/ModelPath/UserPath/User.php',
            ],
            [
                'MyVendor\MyPackage\ModelPath\UserPath\User',
                'src',
                '\MyVendor\MyPackage\\',
                'src/ModelPath/UserPath/User',
            ],
        ];
    }

    private function filterDirectoryToNamespace(): callable
    {
        $filter = new FilterChain();
        $filter->attach(new SeparatorToSeparator(DIRECTORY_SEPARATOR, '|'));
        $filter->attach(new SeparatorToSeparator('|', '\\\\'));

        return $filter;
    }

    private function filterNamespaceToDirectory(): callable
    {
        $filter = new FilterChain();
        $filter->attach(new SeparatorToSeparator('\\', '|'));
        $filter->attach(new SeparatorToSeparator('|', DIRECTORY_SEPARATOR));

        return $filter;
    }
}
