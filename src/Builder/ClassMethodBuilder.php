<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Builder;

use OpenCodeModeling\CodeAst\Code\BodyGenerator;
use OpenCodeModeling\CodeAst\Code\ClassConstGenerator;
use OpenCodeModeling\CodeAst\Code\DocBlock\DocBlock;
use OpenCodeModeling\CodeAst\Code\MethodGenerator;
use OpenCodeModeling\CodeAst\NodeVisitor\ClassMethod;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;

final class ClassMethodBuilder
{
    /** @var string */
    private $name;

    /** @var ParameterBuilder[] */
    private $parameters = [];

    /** @var string */
    private $body = '';

    /** @var string|null */
    private $returnType;

    /**
     * @var int
     */
    private $visibility;

    /** @var bool */
    private $typed = false;

    /**
     * @var string|null
     */
    private $docBlockComment;

    /**
     * @var string|null
     */
    private $returnTypeDocBlockHint;

    /**
     * @var DocBlock|null
     */
    private $docBlock;

    private function __construct()
    {
    }

    public static function fromNode(Node\Stmt\ClassMethod $node): self
    {
        $self = new self();

        $self->name = $node->name->toString();
        $self->returnType = $node->returnType ? $node->returnType->toString() : null;
        $self->visibility = $node->flags;

        foreach ($node->params as $param) {
            $self->parameters[] = ParameterBuilder::fromNode($param);
        }

        if ($self->returnType !== null) {
            $self->typed = true;
        }

        return $self;
    }

    public static function fromScratch(
        string $name,
        bool $typed = true
    ): self {
        $self = new self();
        $self->name = $name;
        $self->typed = $typed;
        $self->visibility = ClassConstGenerator::FLAG_PUBLIC;

        return $self;
    }

    public function setParameters(ParameterBuilder ...$parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function setReturnType(?string $returnType): self
    {
        $this->returnType = $returnType;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ParameterBuilder[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function isTyped(): bool
    {
        return $this->typed;
    }

    public function setPrivate(): self
    {
        $this->visibility = ClassConstGenerator::FLAG_PRIVATE;

        return $this;
    }

    public function setProtected(): self
    {
        $this->visibility = ClassConstGenerator::FLAG_PROTECTED;

        return $this;
    }

    public function setPublic(): self
    {
        $this->visibility = ClassConstGenerator::FLAG_PUBLIC;

        return $this;
    }

    public function getDocBlockComment(): ?string
    {
        return $this->docBlockComment;
    }

    public function setDocBlockComment(?string $docBlockComment): void
    {
        $this->docBlockComment = $docBlockComment;
    }

    public function getReturnTypeDocBlockHint(): string
    {
        return $this->returnTypeDocBlockHint;
    }

    public function setReturnTypeDocBlockHint(?string $typeDocBlockHint): void
    {
        $this->returnTypeDocBlockHint = $typeDocBlockHint;
    }

    public function getDocBlock(): ?DocBlock
    {
        return $this->docBlock;
    }

    public function overrideDocBlock(?DocBlock $docBlock): void
    {
        $this->docBlock = $docBlock;
    }

    public function generate(Parser $parser): NodeVisitor
    {
        return new ClassMethod($this->methodGenerator($parser));
    }

    private function methodGenerator(Parser $parser): MethodGenerator
    {
        $flags = $this->visibility;

        $body = new BodyGenerator($parser, $this->body);

        $methodGenerator = new MethodGenerator(
            $this->name,
            \array_map(static function (ParameterBuilder $builder) {
                return $builder->generate();
            }, $this->parameters),
            $flags,
            $body
        );

        $methodGenerator->setReturnType($this->returnType);
        $methodGenerator->setTyped($this->typed);
        $methodGenerator->setDocBlockComment($this->docBlockComment);
        $methodGenerator->setReturnTypeDocBlockHint($this->returnTypeDocBlockHint);
        $methodGenerator->overrideDocBlock($this->docBlock);

        return $methodGenerator;
    }

    public function injectVisitors(NodeTraverser $nodeTraverser, Parser $parser): void
    {
        $nodeTraverser->addVisitor($this->generate($parser));
    }
}
