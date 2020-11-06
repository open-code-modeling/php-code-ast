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
    private $namespaceUse = [];

    /** @var string[] */
    private $traits = [];

    /** @var ClassConstBuilder[] */
    private $constants = [];

    /** @var ClassPropertyBuilder[] */
    private $properties = [];

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
        string $className,
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

    public function injectVisitors(NodeTraverser $nodeTraverser): void
    {
        foreach ($this->generate() as $visitor) {
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

    public function setImplements(string ...$interfaces): self
    {
        $this->implements = $interfaces;

        return $this;
    }

    public function setNamespaceUse(string ...$namespaces): self
    {
        $this->namespaceUse = $namespaces;

        return $this;
    }

    public function setUseTrait(string ...$traits): self
    {
        $this->traits = $traits;

        return $this;
    }

    public function setConstants(ClassConstBuilder ...$constants): self
    {
        $this->constants = $constants;

        return $this;
    }

    public function setProperties(ClassPropertyBuilder ...$properties): self
    {
        $this->properties = $properties;

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
     * @return string[]
     */
    public function getNamespaceUse(): array
    {
        return $this->namespaceUse;
    }

    /**
     * @return string[]
     */
    public function getUseTrait(): array
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
     * @return NodeVisitor[]
     */
    public function generate(): array
    {
        /** @var NodeVisitor[] $visitors */
        $visitors = [];

        if ($this->strict) {
            $visitors[] = new StrictType();
        }

        if ($this->namespace) {
            $visitors[] = new ClassNamespace($this->namespace);
        }
        if ($this->namespaceUse) {
            $visitors[] = new NamespaceUse(...$this->namespaceUse);
        }

        $visitors[] = new ClassFile($this->classGenerator());

        if ($this->extends) {
            $visitors[] = new ClassExtends($this->extends);
        }
        if ($this->implements) {
            $visitors[] = new ClassImplements(...$this->implements);
        }
        if ($this->traits) {
            $visitors[] = new ClassUseTrait(...$this->traits);
        }

        if (\count($this->constants) > 0) {
            \array_push(
                $visitors,
                ...\array_map(
                    static function (ClassConstBuilder $const) {
                        return $const->generate();
                    },
                    $this->constants
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
                    $this->properties
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
                $this->namespaceUse[] = $node->name instanceof Node\Name\FullyQualified
                    ? '\\' . $node->name->toString()
                    : $node->name->toString();
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
                $this->implements = \array_map(
                    static function (Node\Name $name) {
                        return $name instanceof Node\Name\FullyQualified
                            ? '\\' . $name->toString()
                            : $name->toString();
                    },
                    $node->implements
                );
                break;
            case $node instanceof Node\Stmt\TraitUse:
                $this->traits = \array_map(
                    static function (Node\Name $name) {
                        return $name instanceof Node\Name\FullyQualified
                            ? '\\' . $name->toString()
                            : $name->toString();
                    },
                    $node->traits
                );
                break;
            case $node instanceof Node\Stmt\ClassConst:
                $this->constants[] = ClassConstBuilder::fromNode($node);
                break;
            case $node instanceof Node\Stmt\Property:
                $this->properties[] = ClassPropertyBuilder::fromNode($node);
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
