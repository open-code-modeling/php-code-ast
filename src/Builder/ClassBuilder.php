<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassExtends;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassImplements;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassUseTrait;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;

final class ClassBuilder
{
    /** @var string|null */
    private $namespace;

    /** @var string|null */
    private $name;

    /** @var bool */
    private $strict = false;

    /** @var bool */
    private $typed = false;

    /** @var bool */
    private $final = false;

    /** @var bool */
    private $abstract = false;

    /** @var string|null */
    private $extends;

    /** @var string[] */
    private $implements = [];

    /** @var string[] */
    private $namespaceImports = [];

    /** @var string[] */
    private $traits = [];

    /** @var ClassConstBuilder[] */
    private $constants = [];

    /** @var ClassPropertyBuilder[] */
    private $properties = [];

    /** @var ClassMethodBuilder[] */
    private $methods = [];

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
        ?string $className,
        string $namespace = null,
        bool $typed = true,
        bool $strict = true
    ): self {
        $self = new self();
        $self->name = $className;
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

    public function setFinal(bool $final): self
    {
        $this->final = $final;

        return $this;
    }

    public function setAbstract(bool $abstract): self
    {
        $this->abstract = $abstract;

        return $this;
    }

    public function setExtends(string $extends): self
    {
        $this->extends = $extends;

        return $this;
    }

    public function setImplements(string ...$implements): self
    {
        $this->implements = [];

        foreach ($implements as $implement) {
            $this->implements[$implement] = $implement;
        }

        return $this;
    }

    /**
     * Adds an implement definition which not already exists
     *
     * @param string ...$implements
     * @return $this
     */
    public function addImplement(string ...$implements): self
    {
        foreach ($implements as $implement) {
            $this->implements[$implement] = $implement;
        }

        return $this;
    }

    public function hasImplement(string $implement): bool
    {
        return isset($this->implements[$implement]);
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
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
     * @param string ...$traits
     * @return $this
     */
    public function setTraits(string ...$traits): self
    {
        $this->traits = [];

        foreach ($traits as $trait) {
            $this->traits[$trait] = $trait;
        }

        return $this;
    }

    /**
     * Adds a trait which not already exists
     *
     * @param string ...$traits
     * @return $this
     */
    public function addTrait(string ...$traits): self
    {
        foreach ($traits as $trait) {
            $this->traits[$trait] = $trait;
        }

        return $this;
    }

    /**
     * Removing will not work on existing files
     *
     * @param string ...$traits
     * @return $this
     */
    public function removeTrait(string ...$traits): self
    {
        foreach ($traits as $trait) {
            unset($this->traits[$trait]);
        }

        return $this;
    }

    public function hasTrait(string $trait): bool
    {
        return isset($this->traits[$trait]);
    }

    /**
     * @deprecated Use setTraits()
     * @param string ...$traits
     * @return self
     */
    public function setUseTrait(string ...$traits): self
    {
        return $this->setTraits(...$traits);
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

    /**
     * Replacing will not work on existing files
     *
     * @param ClassPropertyBuilder ...$properties
     * @return $this
     */
    public function setProperties(ClassPropertyBuilder ...$properties): self
    {
        $this->properties = [];

        foreach ($properties as $property) {
            $this->properties[$property->getName()] = $property;
        }

        return $this;
    }

    /**
     * Adds a property which not already exists
     *
     * @param ClassPropertyBuilder ...$properties
     * @return $this
     */
    public function addProperty(ClassPropertyBuilder ...$properties): self
    {
        foreach ($properties as $property) {
            $this->properties[$property->getName()] = $property;
        }

        return $this;
    }

    /**
     * Removing will not work on existing files
     *
     * @param string ...$propertyNames
     * @return $this
     */
    public function removeProperty(string ...$propertyNames): self
    {
        foreach ($propertyNames as $propertyName) {
            unset($this->properties[$propertyName]);
        }

        return $this;
    }

    public function hasProperty(string $propertyName): bool
    {
        return isset($this->properties[$propertyName]);
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

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setStrict(bool $strict): self
    {
        $this->strict = $strict;

        return $this;
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

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function getExtends(): ?string
    {
        return $this->extends;
    }

    /**
     * @return string[]
     */
    public function getImplements(): array
    {
        return $this->implements;
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
     * @deprecated Use getTraits()
     * @return string[]
     */
    public function getUseTrait(): array
    {
        return $this->traits;
    }

    /**
     * @return string[]
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    /**
     * @return ClassConstBuilder[]
     */
    public function getConstants(): array
    {
        return $this->constants;
    }

    /**
     * @return ClassPropertyBuilder[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return ClassMethodBuilder[]
     */
    public function getMethods(): array
    {
        return $this->methods;
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
     * @param callable $sort (ClassPropertyBuilder $a, ClassPropertyBuilder $b)
     * @return $this
     */
    public function sortProperties(callable $sort): self
    {
        \uasort($this->properties, $sort);

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
    public function sortTraits(callable $sort): self
    {
        \uasort($this->traits, $sort);

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

    /**
     * @deprecated Use sortNamespaceImports()
     * @param callable $sort
     * @return $this
     */
    public function sortNamespaceUse(callable $sort): self
    {
        return $this->sortNamespaceImports($sort);
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

        $visitors[] = new ClassFile($this->classGenerator());

        if ($this->extends) {
            $visitors[] = new ClassExtends($this->extends);
        }
        if ($this->implements) {
            $visitors[] = new ClassImplements(...\array_values($this->implements));
        }
        if ($this->traits) {
            $visitors[] = new ClassUseTrait(...\array_reverse(\array_values($this->traits)));
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
        if (\count($this->properties) > 0) {
            \array_push(
                $visitors,
                ...\array_map(
                    static function (ClassPropertyBuilder $property) {
                        return $property->generate();
                    },
                    \array_values($this->properties)
                )
            );
        }
        if (\count($this->methods) > 0) {
            \array_push(
                $visitors,
                ...\array_map(
                    static function (ClassMethodBuilder $method) use ($parser) {
                        return $method->generate($parser);
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
            case $node instanceof Node\Stmt\Class_:
                $this->name = $node->name->name;
                $this->final = $node->isFinal();

                if ($node->extends !== null) {
                    $this->extends = $node->extends instanceof Node\Name\FullyQualified
                        ? '\\' . $node->extends->toString()
                        : $node->extends->toString();
                }

                foreach ($node->stmts as $stmt) {
                    $this->unpackNode($stmt);
                }

                foreach ($node->implements as $implement) {
                    $name = $implement instanceof Node\Name\FullyQualified
                        ? '\\' . $implement->toString()
                        : $implement->toString();
                    $this->implements[$name] = $name;
                }
                break;
            case $node instanceof Node\Stmt\TraitUse:
                foreach ($node->traits as $trait) {
                    $name = $trait instanceof Node\Name\FullyQualified
                        ? '\\' . $trait->toString()
                        : $trait->toString();
                    $this->traits[$name] = $name;
                }
                break;
            case $node instanceof Node\Stmt\ClassConst:
                $constant = ClassConstBuilder::fromNode($node);
                $this->constants[$constant->getName()] = $constant;
                break;
            case $node instanceof Node\Stmt\Property:
                $property = ClassPropertyBuilder::fromNode($node);
                $this->properties[$property->getName()] = $property;
                break;
            case $node instanceof Node\Stmt\ClassMethod:
                $method = ClassMethodBuilder::fromNode($node);
                $this->methods[$method->getName()] = $method;
                break;
            default:
                break;
        }
    }

    private function classGenerator(): ClassGenerator
    {
        $flags = 0;

        if ($this->final) {
            $flags |= ClassGenerator::FLAG_FINAL;
        }
        if ($this->abstract) {
            $flags |= ClassGenerator::FLAG_ABSTRACT;
        }

        return new ClassGenerator($this->name, $flags);
    }
}
