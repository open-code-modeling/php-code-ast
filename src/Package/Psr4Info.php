<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Package;

use Composer\Autoload\ClassLoader;

final class Psr4Info implements ClassInfo
{
    /**
     * source folder
     *
     * @var string
     */
    private $sourceFolder;

    /**
     * Package prefix
     *
     * @var string
     */
    private $packagePrefix;

    /**
     * @var callable
     */
    private $filterDirectoryToNamespace;

    /**
     * @var callable
     */
    protected $filterNamespaceToDirectory;

    /**
     * Configure PSR-4 meta info
     *
     * @param string $sourceFolder Absolute path to the source folder
     * @param string $packagePrefix Package prefix which is used as class namespace
     * @param callable $filterDirectoryToNamespace Callable to filter a directory to a namespace
     * @param callable $filterNamespaceToDirectory Callable to filter a namespace to a directory
     */
    public function __construct(
        string $sourceFolder,
        string $packagePrefix,
        callable $filterDirectoryToNamespace,
        callable $filterNamespaceToDirectory
    ) {
        $this->sourceFolder = \rtrim($sourceFolder, '/');
        $this->packagePrefix = \trim($packagePrefix, '\\');
        $this->filterDirectoryToNamespace = $filterDirectoryToNamespace;
        $this->filterNamespaceToDirectory = $filterNamespaceToDirectory;
    }

    public function getPackagePrefix(): string
    {
        return $this->packagePrefix;
    }

    /**
     * Class namespace is determined by package prefix, source folder and given path.
     *
     * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md#3-examples
     *
     * @param string $path
     * @return string
     */
    public function getClassNamespaceFromPath(string $path): string
    {
        $namespace = ($this->filterDirectoryToNamespace)($this->normalizePath($path));

        return $this->normalizeNamespace($this->getPackagePrefix() . '\\' . $namespace);
    }

    public function getFullyQualifiedClassNameFromFilename(string $filename): string
    {
        [$path, $name] = $this->getPathAndNameFromFilename($filename);

        return $this->getClassNamespaceFromPath($path) . '\\' . $name;
    }

    public function getClassNamespace(string $fcqn): string
    {
        $namespace = $this->normalizeNamespace($fcqn);
        $namespace = \substr($namespace, 0, \strrpos($namespace, '/'));

        return $this->normalizeNamespace($this->getPackagePrefix() . '\\' . $namespace);
    }

    public function getClassName(string $fqcn): string
    {
        $fqcn = $this->normalizeNamespace($fqcn);

        return \trim(\substr($fqcn, \strrpos($fqcn, '\\')), '\\');
    }

    public function getPath(string $fqcn): string
    {
        $fqcn = $this->normalizeNamespace($fqcn);
        $namespace = \str_replace($this->getPackagePrefix(), '', $fqcn);
        $namespace = \ltrim(\substr($namespace, 0, \strrpos($namespace, '\\')), '\\');

        return ($this->filterNamespaceToDirectory)($namespace);
    }

    public function getFilenameFromPathAndName(string $path, string $name): string
    {
        $filePath = $this->getSourceFolder() . DIRECTORY_SEPARATOR;

        if ($path = \trim($path, '/')) {
            $filePath .= $this->normalizePath($path) . DIRECTORY_SEPARATOR;
        }

        return $filePath . $name . '.php';
    }

    public function getPathAndNameFromFilename(string $filename): array
    {
        $path = \substr($filename, 0, \strrpos($filename, DIRECTORY_SEPARATOR));
        $name = \substr($filename, \strrpos($filename, DIRECTORY_SEPARATOR) + 1);
        $name = \substr($name, 0, \strpos($name, '.') ?: \strlen($name));

        return [$this->normalizePath($path), $name];
    }

    public function isValidPath(string $filenameOrPath): bool
    {
        $path = \substr($filenameOrPath, 0, \strrpos($filenameOrPath, DIRECTORY_SEPARATOR));

        if (0 === \strpos($path, $this->sourceFolder)) {
            return true;
        }

        return false;
    }

    public function getSourceFolder(): string
    {
        return $this->sourceFolder;
    }

    /**
     * Removes duplicates of backslashes and trims backslashes
     *
     * @param string $namespace
     * @return string
     */
    private function normalizeNamespace(string $namespace): string
    {
        $namespace = \str_replace('\\\\', '\\', $namespace);

        return \trim($namespace, '\\');
    }

    /**
     * Remove source folder from path
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return \preg_replace('/^' . \addcslashes($this->sourceFolder, '/') . '\//', '', $path);
    }

    /**
     * Creates an instance of the class Psr4Info based on the Composer configuration.
     *
     * @param ClassLoader $classLoader Composer ClassLoader instance
     * @param callable $filterDirectoryToNamespace Callable to filter a directory to a namespace
     * @param callable $filterNamespaceToDirectory  Callable to filter a namespace to a directory
     * @param string $exclude Specifies which path should be ignored
     * @return array
     */
    public static function fromComposer(
        ClassLoader $classLoader,
        callable $filterDirectoryToNamespace,
        callable $filterNamespaceToDirectory,
        string $exclude = 'vendor' . DIRECTORY_SEPARATOR
    ): array {
        $namespaces = [];

        foreach ($classLoader->getPrefixesPsr4() as $namespace => $paths) {
            $realpath = \preg_replace('/^' . \addcslashes(\getcwd(), '/') . '\//', '', \realpath($paths[0]));
            if (false !== \stripos($realpath, $exclude)) {
                continue;
            }
            $classInfo = new self($realpath, $namespace, $filterDirectoryToNamespace, $filterNamespaceToDirectory);

            $namespaces[] = $classInfo;
        }

        return $namespaces;
    }
}
