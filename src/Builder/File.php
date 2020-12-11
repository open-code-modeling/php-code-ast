<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;

interface File
{
    public function getNamespace(): ?string;

    public function getName(): ?string;

    /**
     * @param Parser $parser
     * @return NodeVisitor[]
     */
    public function generate(Parser $parser): array;

    public function injectVisitors(NodeTraverser $nodeTraverser, Parser $parser): void;

    public function hasConstant(string $constantName): bool;

    /**
     * Removing will not work on existing files
     *
     * @param string ...$constants
     * @return $this
     */
    public function removeConstant(string ...$constants): self;

    /**
     * Uses uasort internally
     *
     * @param callable $sort (ClassConstBuilder $a, ClassConstBuilder $b)
     * @return self
     */
    public function sortConstants(callable $sort): self;

    public function hasMethod(string $methodName): bool;

    /**
     * Removing will not work on existing files
     *
     * @param string ...$methodNames
     * @return $this
     */
    public function removeMethod(string ...$methodNames): self;

    /**
     * Uses uasort internally
     *
     * @param callable $sort (ClassMethodBuilder $a, ClassMethodBuilder $b)
     * @return self
     */
    public function sortMethods(callable $sort): self;

    /**
     * Uses uasort internally
     *
     * @param callable $sort (string $a, string $b)
     * @return self
     */
    public function sortNamespaceImports(callable $sort): self;

    /**
     * Removing will not work on existing files
     *
     * @param string ...$namespaceImports
     * @return $this
     */
    public function removeNamespaceImport(string ...$namespaceImports): self;

    public function hasNamespaceImport(string $namespace): bool;
}
