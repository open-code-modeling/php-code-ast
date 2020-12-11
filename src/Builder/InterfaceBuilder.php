<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Code\InterfaceGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\InterfaceExtends;
use OpenCodeModeling\CodeAst\NodeVisitor\InterfaceFile;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;

final class InterfaceBuilder implements File
{
    /** @var string|null */
    private ?string $namespace = null;

    /** @var string|null */
    private ?string $name = null;

    /** @var bool */
    private bool $strict = false;

    /** @var bool */
    private bool $typed = true;

    /** @var string[] */
    private array $extends = [];

    /** @var string[] */
    private array $namespaceImports = [];

    /** @var ClassConstBuilder[] */
    private array $constants = [];

    /** @var ClassMethodBuilder[] */
    private array $methods = [];

    private function __construct()
    {
    }

    public static function fromNodes(Node ...$nodes): self
    {
        $self = new self();

        foreach ($nodes as $node) {
            $self->unpackNode($node);
        }

        return $self;
    }

    public static function fromScratch(
        string $interfaceName,
        string $namespace = null,
        bool $typed = true,
        bool $strict = true
    ): self {
        $self = new self();
        $self->name = $interfaceName;
        $self->namespace = $namespace;
        $self->typed = $typed;
        $self->strict = $strict;

        return $self;
    }

    public function injectVisitors(NodeTraverser $nodeTraverser, Parser $parser): void
    {
        foreach ($this->generate($parser) as $visitor) {
            $nodeTraverser->addVisitor($visitor);
        }
    }

    public function setExtends(string ...$extends): self
    {
        $this->extends = [];

        foreach ($extends as $extend) {
            $this->extends[$extend] = $extend;
        }

        return $this;
    }

    /**
     * Adds an extend definition which not already exists
     *
     * @param string ...$extends
     * @return $this
     */
    public function addExtend(string ...$extends): self
    {
        foreach ($extends as $extend) {
            $this->extends[$extend] = $extend;
        }

        return $this;
    }

    public function hasExtend(string $extend): bool
    {
        return isset($this->extends[$extend]);
    }

    /**
     * Replacing will not work on existing files
     *
     * @param string ...$namespaceImports
     * @return $this
     */
    public function setNamespaceImports(string ...$namespaceImports): self
    {
        $this->namespaceImports = [];

        foreach ($namespaceImports as $namespaceImport) {
            $this->namespaceImports[$namespaceImport] = $namespaceImport;
        }

        return $this;
    }

    /**
     * Adds a namespace import which not already exists
     *
     * @param string ...$namespaceImports
     * @return $this
     */
    public function addNamespaceImport(string ...$namespaceImports): self
    {
        foreach ($namespaceImports as $namespaceImport) {
            $this->namespaceImports[$namespaceImport] = $namespaceImport;
        }

        return $this;
    }

    /**
     * Removing will not work on existing files
     *
     * @param string ...$namespaceImports
     * @return $this
     */
    public function removeNamespaceImport(string ...$namespaceImports): self
    {
        foreach ($namespaceImports as $namespaceImport) {
            unset($this->namespaceImports[$namespaceImport]);
        }

        return $this;
    }

    public function hasNamespaceImport(string $namespace): bool
    {
        return isset($this->namespaceImports[$namespace]);
    }

    /**
     * @deprecated Use setNamespaceImports()
     * @param string ...$namespaces
     * @return self
     */
    public function setNamespaceUse(string ...$namespaces): self
    {
        return $this->setNamespaceImports(...$namespaces);
    }

    /**
     * Replacing will not work on existing files
     *
     * @param ClassConstBuilder ...$constants
     * @return $this
     */
    public function setConstants(ClassConstBuilder ...$constants): self
    {
        $this->constants = [];

        foreach ($constants as $constant) {
            $this->constants[$constant->getName()] = $constant;
        }

        return $this;
    }

    /**
     * Adds a constant which not already exists
     *
     * @param ClassConstBuilder ...$constants
     * @return $this
     */
    public function addConstant(ClassConstBuilder ...$constants): self
    {
        foreach ($constants as $constant) {
            $this->constants[$constant->getName()] = $constant;
        }

        return $this;
    }

    /**
     * Removing will not work on existing files
     *
     * @param string ...$constants
     * @return $this
     */
    public function removeConstant(string ...$constants): self
    {
        foreach ($constants as $constant) {
            unset($this->constants[$constant]);
        }

        return $this;
    }

    public function hasConstant(string $constantName): bool
    {
        return isset($this->constants[$constantName]);
    }

    public function setMethods(ClassMethodBuilder ...$methods): self
    {
        $this->methods = [];

        foreach ($methods as $method) {
            $this->methods[$method->getName()] = $method;
        }

        return $this;
    }

    /**
     * Adds a method and overrides existing method if any.
     *
     * @param ClassMethodBuilder ...$methods
     * @return $this
     */
    public function addMethod(ClassMethodBuilder ...$methods): self
    {
        foreach ($methods as $method) {
            $this->methods[$method->getName()] = $method;
        }

        return $this;
    }

    /**
     * Removing will not work on existing files
     *
     * @param string ...$methodNames
     * @return $this
     */
    public function removeMethod(string ...$methodNames): self
    {
        foreach ($methodNames as $methodName) {
            unset($this->methods[$methodName]);
        }

        return $this;
    }

    public function hasMethod(string $methodName): bool
    {
        return isset($this->methods[$methodName]);
    }

    /**
     * Uses uasort internally
     *
     * @param callable $sort (ClassConstBuilder $a, ClassConstBuilder $b)
     * @return $this
     */
    public function sortConstants(callable $sort): self
    {
        \uasort($this->constants, $sort);

        return $this;
    }

    /**
     * Uses uasort internally
     *
     * @param callable $sort (ClassMethodBuilder $a, ClassMethodBuilder $b)
     * @return $this
     */
    public function sortMethods(callable $sort): self
    {
        \uasort($this->methods, $sort);

        return $this;
    }

    /**
     * Uses uasort internally
     *
     * @param callable $sort (string $a, string $b)
     * @return $this
     */
    public function sortNamespaceImports(callable $sort): self
    {
        \uasort($this->namespaceImports, $sort);

        return $this;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function setTyped(bool $typed): self
    {
        $this->typed = $typed;

        return $this;
    }

    public function isTyped(): bool
    {
        return $this->typed;
    }

    public function getExtends(): array
    {
        return $this->extends;
    }

    /**
     * @deprecated Use namespaceImports()
     * @return string[]
     */
    public function getNamespaceUse(): array
    {
        return $this->namespaceImports;
    }

    /**
     * @return string[]
     */
    public function getNamespaceImports(): array
    {
        return $this->namespaceImports;
    }

    /**
     * @return ClassConstBuilder[]
     */
    public function getConstants(): array
    {
        return $this->constants;
    }

    /**
     * @param Parser $parser
     * @return NodeVisitor[]
     */
    public function generate(Parser $parser): array
    {
        /** @var NodeVisitor[] $visitors */
        $visitors = [];

        if ($this->strict) {
            $visitors[] = new StrictType();
        }

        if ($this->namespace) {
            $visitors[] = new ClassNamespace($this->namespace);
        }
        if ($this->namespaceImports) {
            $visitors[] = new NamespaceUse(...\array_reverse(\array_values($this->namespaceImports)));
        }

        $visitors[] = new InterfaceFile(new InterfaceGenerator($this->name));

        if ($this->extends) {
            $visitors[] = new InterfaceExtends(...\array_values($this->extends));
        }

        if (\count($this->constants) > 0) {
            \array_push(
                $visitors,
                ...\array_map(
                    static function (ClassConstBuilder $const) {
                        return $const->generate();
                    },
                    \array_values($this->constants)
                )
            );
        }

        if (\count($this->methods) > 0) {
            \array_push(
                $visitors,
                ...\array_map(
                    static function (ClassMethodBuilder $method) use ($parser) {
                        return $method->generate($parser, true);
                    },
                    \array_values($this->methods)
                )
            );
        }

        return $visitors;
    }

    private function unpackNode(Node $node): void
    {
        switch (true) {
            case $node instanceof Node\Stmt\Declare_:
                if ($node->declares[0]->key->name === 'strict_types') {
                    $this->strict = true;
                }
                break;
            case $node instanceof Node\Stmt\Namespace_:
                $this->namespace = $node->name->toString();

                foreach ($node->stmts as $stmt) {
                    $this->unpackNode($stmt);
                }
                break;
            case $node instanceof Node\Stmt\Use_:
                foreach ($node->uses as $use) {
                    $this->unpackNode($use);
                }
                break;
            case $node instanceof Node\Stmt\UseUse:
                $namespaceImport = $node->name instanceof Node\Name\FullyQualified
                    ? '\\' . $node->name->toString()
                    : $node->name->toString();
                $this->namespaceImports[$namespaceImport] = $namespaceImport;
                break;
            case $node instanceof Node\Stmt\Interface_:
                $this->name = $node->name->name;

                foreach ($node->extends as $extend) {
                    $name = $extend instanceof Node\Name\FullyQualified
                        ? '\\' . $extend->toString()
                        : $extend->toString();
                    $this->extends[$name] = $name;
                }

                foreach ($node->stmts as $stmt) {
                    $this->unpackNode($stmt);
                }
                break;
            case $node instanceof Node\Stmt\ClassConst:
                $constant = ClassConstBuilder::fromNode($node);
                $this->constants[$constant->getName()] = $constant;
                break;
            case $node instanceof Node\Stmt\ClassMethod:
                $method = ClassMethodBuilder::fromNode($node);
                $this->methods[$method->getName()] = $method;
                break;
            default:
                break;
        }
    }
}
