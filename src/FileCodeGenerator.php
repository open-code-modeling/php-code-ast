<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst;

use OpenCodeModeling\CodeAst\Builder\ClassBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassConstBuilder;
use OpenCodeModeling\CodeAst\Builder\ClassMethodBuilder;
use OpenCodeModeling\CodeAst\Builder\File;
use OpenCodeModeling\CodeAst\Builder\FileCollection;
use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Package\ClassInfo;
use OpenCodeModeling\CodeAst\Package\ClassInfoList;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;

final class FileCodeGenerator
{
    private ClassInfoList $classInfoList;
    private Parser $parser;
    private PrettyPrinterAbstract $printer;

    public function __construct(
        Parser $parser,
        PrettyPrinterAbstract $printer,
        ClassInfoList $classInfoList
    ) {
        $this->parser = $parser;
        $this->printer = $printer;
        $this->classInfoList = $classInfoList;
    }

    /**
     * Returns the generated code of provided file collection
     *
     * @param FileCollection $fileCollection
     * @param callable|null $currentFileAst Callable to return current file AST, if null, file will be overwritten
     * @return array<string, string> List of filename => code
     */
    public function generateFiles(
        FileCollection $fileCollection,
        callable $currentFileAst = null
    ): array {
        $files = [];

        if ($currentFileAst === null) {
            $currentFileAst = static function (File $file, ClassInfo $classInfo) {
                return [];
            };
        }

        $previousNamespace = '__invalid//namespace__';

        foreach ($fileCollection as $classBuilder) {
            if ($previousNamespace !== $classBuilder->getNamespace()) {
                $previousNamespace = $classBuilder->getNamespace();
                $classInfo = $this->classInfoList->classInfoForNamespace($previousNamespace);
                $path = $classInfo->getPath($classBuilder->getNamespace() . '\\' . $classBuilder->getName());
            }
            $filename = $classInfo->getFilenameFromPathAndName($path, $classBuilder->getName());

            $nodeTraverser = new NodeTraverser();
            $classBuilder->injectVisitors($nodeTraverser, $this->parser);

            $files[$filename] = $this->printer->prettyPrintFile(
                $nodeTraverser->traverse($currentFileAst($classBuilder, $classInfo))
            );
        }

        return $files;
    }

    /**
     * Generation of getter methods. Use $skip callable to skip generation e. g. for value objects
     *
     * @param FileCollection $classBuilderCollection Only ClassBuilder objects are considered
     * @param bool $typed Should the generated code be typed
     * @param callable $methodNameFilter Filter the property name to your desired method name e.g. with "get" prefix
     * @param callable|null $skip Check method to skip getter methods e.g. for value objects
     */
    public function addGetterMethodsForProperties(
        FileCollection $classBuilderCollection,
        bool $typed,
        callable $methodNameFilter,
        callable $skip = null
    ): void {
        if ($skip === null) {
            $skip = static function (ClassBuilder $classBuilder) {
                return false;
            };
        }

        foreach ($classBuilderCollection as $classBuilder) {
            if (! $classBuilder instanceof ClassBuilder) {
                continue;
            }
            foreach ($classBuilder->getProperties() as $classPropertyBuilder) {
                $methodName = ($methodNameFilter)($classPropertyBuilder->getName());

                if (true === ($skip)($classBuilder)
                    || $classBuilder->hasMethod($methodName)) {
                    continue 2;
                }
                $classBuilder->addMethod(
                    ClassMethodBuilder::fromScratch($methodName, $typed)
                        ->setReturnType($classPropertyBuilder->getType())
                        ->setReturnTypeDocBlockHint($classPropertyBuilder->getTypeDocBlockHint())
                        ->setBody('return $this->' . $classPropertyBuilder->getName() . ';')
                );
            }
        }
    }

    /**
     * Generation of constants for each property. Use $skip callable to skip generation e. g. for value objects
     *
     * @param FileCollection $fileCollection Only ClassBuilder objects are considered
     * @param callable $constantNameFilter Converts the name to a proper class constant name
     * @param callable $constantValueFilter Converts the name to a proper class constant value e.g. snake_case or camelCase
     * @param callable|null $skip Check method to skip getter methods e.g. for value objects
     * @param int $visibility Visibility of the class constant
     */
    public function addClassConstantsForProperties(
        FileCollection $fileCollection,
        callable $constantNameFilter,
        callable $constantValueFilter,
        callable $skip = null,
        int $visibility = ClassConstGenerator::FLAG_PUBLIC
    ): void {
        if ($skip === null) {
            $skip = static function (ClassBuilder $classBuilder) {
                return false;
            };
        }

        foreach ($fileCollection as $classBuilder) {
            if (! $classBuilder instanceof ClassBuilder) {
                continue;
            }
            foreach ($classBuilder->getProperties() as $classPropertyBuilder) {
                $constantName = ($constantNameFilter)($classPropertyBuilder->getName());

                if (true === ($skip)($classBuilder)
                    || $classBuilder->hasConstant($constantName)) {
                    continue 2;
                }
                $classBuilder->addConstant(
                    ClassConstBuilder::fromScratch(
                        $constantName,
                        ($constantValueFilter)($classPropertyBuilder->getName()),
                        $visibility
                    )
                );
            }
        }
    }
}
