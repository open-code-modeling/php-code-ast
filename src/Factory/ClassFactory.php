<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Factory;

use OpenCodeModeling\CodeAst\Code\ClassGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassExtends;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassFile;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassImplements;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassNamespace;
use OpenCodeModeling\CodeAst\NodeVisitor\NamespaceUse;
use OpenCodeModeling\CodeAst\NodeVisitor\StrictType;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;

final class ClassFactory
{
    /** @var string|null */
    private $namespace;

    /** @var string|null */
    private $name;

    /** @var bool */
    private $strict;

    /** @var bool */
    private $typed;

    /** @var bool */
    private $final = false;

    /** @var bool */
    private $abstract = false;

    /** @var string */
    private $extends;

    /** @var string[] */
    private $implements = [];

    /** @var string[] */
    private $namespaceUse = [];

    private function __construct()
    {
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

        return $visitors;
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
