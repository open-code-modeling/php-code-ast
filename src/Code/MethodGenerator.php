<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use OpenCodeModeling\CodeAst\Code\DocBlock\DocBlock;
use OpenCodeModeling\CodeAst\Code\DocBlock\Tag\ParamTag;
use OpenCodeModeling\CodeAst\Code\DocBlock\Tag\ReturnTag;
use OpenCodeModeling\CodeAst\Exception;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Code is largely lifted from the Zend\Code\Generator\MethodGenerator implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class MethodGenerator extends AbstractMemberGenerator
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var BodyGenerator|null
     */
    private $body;

    /**
     * @var null|TypeGenerator
     */
    private $returnType;

    /**
     * @var bool
     */
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

    /**
     * @param string $name
     * @param array $parameters
     * @param int $flags
     * @param BodyGenerator|null $body
     */
    public function __construct(
        string $name,
        array $parameters = [],
        $flags = self::FLAG_PUBLIC,
        BodyGenerator $body = null
    ) {
        $this->setName($name);

        if ($parameters) {
            $this->setParameters($parameters);
        }
        if ($flags !== self::FLAG_PUBLIC) {
            $this->setFlags($flags);
        }
        if ($body) {
            $this->setBody($body);
        }
    }

    /**
     * @param array $parameters
     * @return MethodGenerator
     */
    public function setParameters(array $parameters): self
    {
        foreach ($parameters as $parameter) {
            $this->setParameter($parameter);
        }

        return $this;
    }

    /**
     * @param ParameterGenerator|array|string $parameter
     * @return MethodGenerator
     * @throws Exception\InvalidArgumentException
     */
    public function setParameter($parameter): self
    {
        if (\is_string($parameter)) {
            $parameter = new ParameterGenerator($parameter);
        }

        if (! $parameter instanceof ParameterGenerator) {
            throw new Exception\InvalidArgumentException(\sprintf(
                '%s is expecting either a string, array or an instance of %s\ParameterGenerator',
                __METHOD__,
                __NAMESPACE__
            ));
        }

        $this->parameters[$parameter->getName()] = $parameter;

        return $this;
    }

    /**
     * @return ParameterGenerator[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param BodyGenerator $body
     * @return MethodGenerator
     */
    public function setBody(BodyGenerator $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getBody(): BodyGenerator
    {
        return $this->body;
    }

    /**
     * @param string|null $returnType
     *
     * @return MethodGenerator
     */
    public function setReturnType($returnType = null): self
    {
        $this->returnType = null === $returnType
            ? null
            : TypeGenerator::fromTypeString($returnType);

        return $this;
    }

    public function getReturnType(): ?TypeGenerator
    {
        return $this->returnType;
    }

    public function getDocBlockComment(): ?string
    {
        return $this->docBlockComment;
    }

    public function setDocBlockComment(?string $docBlockComment): void
    {
        $this->docBlockComment = $docBlockComment;
    }

    public function getReturnTypeDocBlockHint(): ?string
    {
        return $this->returnTypeDocBlockHint;
    }

    public function setReturnTypeDocBlockHint(?string $typeDocBlockHint): void
    {
        $this->returnTypeDocBlockHint = $typeDocBlockHint;
    }

    /**
     * @return bool
     */
    public function getTyped(): bool
    {
        return $this->typed;
    }

    /**
     * @param bool $typed
     */
    public function setTyped(bool $typed): void
    {
        $this->typed = $typed;
    }

    /**
     * Ignores generation of the doc block and uses provided doc block instead.
     *
     * @param DocBlock|null $docBlock
     */
    public function overrideDocBlock(?DocBlock $docBlock): void
    {
        $this->docBlock = $docBlock;
    }

    public function generate(): ClassMethod
    {
        return new ClassMethod(
            $this->getName(),
            [
                'flags' => $this->flags,
                'params' => \array_map(
                    static function (ParameterGenerator $parameter) {
                        return $parameter->generate();
                    },
                    $this->getParameters()
                ),
                'stmts' => $this->body ? $this->body->generate() : null,
                'returnType' => $this->returnType ? $this->returnType->generate() : null,
            ],
            $this->generateAttributes()
        );
    }

    private function generateAttributes(): array
    {
        $attributes = [];

        if ($this->docBlock) {
            return ['comments' => [new Doc($this->docBlock->generate())]];
        }

        if ($this->typed === false || $this->docBlockComment !== null || $this->returnTypeDocBlockHint !== null) {
            $docBlock = new DocBlock($this->docBlockComment);

            foreach ($this->getParameters() as $parameter) {
                if (null === $parameter->getType()) {
                    $types = 'mixed';
                } else {
                    $types = $parameter->getType()->types();
                }
                if ($typeHint = $parameter->getTypeDocBlockHint()) {
                    $types = $typeHint;
                }
                $docBlock->addTag(new ParamTag($parameter->getName(), $types));
            }

            $returnType = null;

            if ($this->returnType) {
                $returnType = $this->returnType->type();
            }
            if ($this->returnTypeDocBlockHint !== null) {
                $returnType = $this->returnTypeDocBlockHint;
            }
            if ($returnType !== null) {
                $docBlock->addTag(new ReturnTag($returnType));
            }

            $attributes = ['comments' => [new Doc($docBlock->generate())]];
        }

        return $attributes;
    }

    public function withoutBody(): self
    {
        $self = clone $this;
        $self->body = null;

        return $self;
    }
}
