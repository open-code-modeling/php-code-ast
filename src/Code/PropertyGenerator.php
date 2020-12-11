<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use OpenCodeModeling\CodeAst\Code\DocBlock\DocBlock;
use OpenCodeModeling\CodeAst\Code\DocBlock\Tag\VarTag;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property;

/**
 * Code is largely lifted from the Zend\Code\Generator\PropertyGenerator implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class PropertyGenerator extends AbstractMemberGenerator
{
    /**
     * @var TypeGenerator|null
     */
    private ?TypeGenerator $type = null;

    /**
     * @var ValueGenerator|null
     */
    private ?ValueGenerator $defaultValue = null;

    /**
     * @var bool
     */
    private bool $typed;

    /**
     * @var string|null
     */
    private ?string $docBlockComment = null;

    /**
     * @var string|null
     */
    private ?string $typeDocBlockHint = null;

    /**
     * @var DocBlock|null
     */
    private ?DocBlock $docBlock = null;

    public function __construct(
        string $name = null,
        string $type = null,
        $defaultValue = null,
        bool $typed = false,
        int $flags = self::FLAG_PRIVATE
    ) {
        if (null !== $name) {
            $this->setName($name);
        }

        if (null !== $type) {
            $this->setType($type);
        }

        if (null !== $defaultValue) {
            $this->setDefaultValue($defaultValue);
        }

        $this->typed = $typed;

        if ($flags !== self::FLAG_PUBLIC) {
            $this->setFlags($flags);
        }
    }

    public function setType(string $type): self
    {
        $this->type = TypeGenerator::fromTypeString($type);

        return $this;
    }

    public function getType(): ?TypeGenerator
    {
        return $this->type;
    }

    public function getDocBlockComment(): ?string
    {
        return $this->docBlockComment;
    }

    public function setDocBlockComment(?string $docBlockComment): self
    {
        $this->docBlockComment = $docBlockComment;

        return $this;
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

    /**
     * @param ValueGenerator|mixed $defaultValue
     * @param string $defaultValueType
     *
     * @return PropertyGenerator
     */
    public function setDefaultValue(
        $defaultValue,
        $defaultValueType = ValueGenerator::TYPE_AUTO
    ): self {
        if (! $defaultValue instanceof ValueGenerator) {
            $defaultValue = new ValueGenerator($defaultValue, $defaultValueType);
        }

        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getDefaultValue(): ?ValueGenerator
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getTypeDocBlockHint(): ?string
    {
        return $this->typeDocBlockHint;
    }

    public function setTypeDocBlockHint(?string $typeDocBlockHint): self
    {
        $this->typeDocBlockHint = $typeDocBlockHint;

        return $this;
    }

    public function generate(): Property
    {
        return new Property(
            $this->flags,
            [
                new Node\Stmt\PropertyProperty(
                    $this->name,
                    $this->defaultValue ? $this->defaultValue->generate() : null
                ),
            ],
            $this->generateAttributes(),
            // @phpstan-ignore-next-line
            $this->typed && null !== $this->type ? $this->type->generate() : null
        );
    }

    private function generateAttributes(): array
    {
        $attributes = [];

        if ($this->docBlock) {
            return ['comments' => [new Doc($this->docBlock->generate())]];
        }

        if ($this->typed === false || $this->docBlockComment !== null || $this->typeDocBlockHint !== null) {
            $docBlockType = null;

            if ($this->type !== null) {
                $docBlockType = new VarTag($this->type->types());
            }
            if ($typeHint = $this->getTypeDocBlockHint()) {
                $docBlockType = new VarTag($typeHint);
            }
            $docBlock = null;

            if ($docBlockType !== null) {
                $docBlock = new DocBlock($this->docBlockComment);
                $docBlock->addTag($docBlockType);
            } elseif ($this->docBlockComment !== null) {
                $docBlock = new DocBlock($this->docBlockComment);
            }

            if ($docBlock !== null) {
                $attributes = ['comments' => [new Doc($docBlock->generate())]];
            }
        }

        return $attributes;
    }
}
