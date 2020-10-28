<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Package;

/**
 * Defines methods to know the package, the namespace and the file path for a class.
 */
interface ClassInfo
{
    /**
     * Returns the package prefix. This is prefixed to every class name.
     *
     * @return string
     */
    public function getPackagePrefix(): string;

    /**
     * Returns the path to the code directory.
     *
     * @return string
     */
    public function getSourceFolder(): string;

    /**
     * Class namespace is determined by package prefix, source folder and given path.
     *
     * @param string $path
     * @return string
     */
    public function getClassNamespaceFromPath(string $path): string;

    /**
     * Returns the class name including namespace based on the file name
     *
     * @param string $filename
     * @return string
     */
    public function getFullyQualifiedClassNameFromFilename(string $filename): string;

    /**
     * Returns the class namespace from FQCN.
     *
     * @param string $fcqn Full qualified class name
     * @return string
     */
    public function getClassNamespace(string $fcqn): string;

    /**
     * Extracts class name from FQCN.
     *
     * @param string $fqcn Full class qualified name
     * @return string Class name
     */
    public function getClassName(string $fqcn): string;

    /**
     * Path is extracted from class name by using package prefix and source folder.
     *
     * @param string $fqcn
     * @return string
     */
    public function getPath(string $fqcn): string;

    /**
     * Returns path to file with source folder.
     *
     * @param string $path Path without source folder
     * @param string $name Class name
     * @return string
     */
    public function getFilenameFromPathAndName(string $path, string $name): string;

    /**
     * Returns the path and name as a list based on the passed file name.
     *
     * @param string $filename
     * @return array
     */
    public function getPathAndNameFromFilename(string $filename): array;

    /**
     * Checks whether the passed path or file name belongs to this namespace or package.
     *
     * @param string $filenameOrPath
     * @return bool
     */
    public function isValidPath(string $filenameOrPath): bool;
}
