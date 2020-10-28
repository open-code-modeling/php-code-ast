<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use PhpParser\Builder;
use PhpParser\Node\Stmt\Class_;

/**
 * Code is largely lifted from the Zend\Code\Generator\ClassGenerator implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class ClassGenerator implements \OpenCodeModeling\CodeAst\StatementGenerator
{
    public const FLAG_ABSTRACT = Class_::MODIFIER_ABSTRACT;
    public const FLAG_FINAL = Class_::MODIFIER_FINAL;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $flags = 0;

    /**
     * @param  string $name
     * @param  array|string $flags
     */
    public function __construct(
        $name,
        $flags = null
    ) {
        $this->setName($name);

        if ($flags !== null) {
            $this->setFlags($flags);
        }
    }

    public function generate(): Class_
    {
        $classBuilder = new Builder\Class_($this->name);
        $node = $classBuilder->getNode();

        $node->flags = $this->flags;

        return $node;
    }

    /**
     * @param  string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param  array|int $flags
     * @return self
     */
    public function setFlags($flags): self
    {
        if (\is_array($flags)) {
            $flagsArray = $flags;
            $flags = 0x00;
            foreach ($flagsArray as $flag) {
                $flags |= $flag;
            }
        }
        // check that visibility is one of three
        $this->flags = $flags;

        return $this;
    }

    /**
     * @param  int $flag
     * @return self
     */
    public function addFlag(int $flag): self
    {
        $this->setFlags($this->flags | $flag);

        return $this;
    }

    /**
     * @param  int $flag
     * @return self
     */
    public function removeFlag(int $flag): self
    {
        $this->setFlags($this->flags & ~$flag);

        return $this;
    }

    /**
     * @param  bool $isAbstract
     * @return self
     */
    public function setAbstract(bool $isAbstract)
    {
        return $isAbstract ? $this->addFlag(self::FLAG_ABSTRACT) : $this->removeFlag(self::FLAG_ABSTRACT);
    }

    /**
     * @return bool
     */
    public function isAbstract(): bool
    {
        return (bool) ($this->flags & self::FLAG_ABSTRACT);
    }

    /**
     * @param  bool $isFinal
     * @return self
     */
    public function setFinal(bool $isFinal)
    {
        return $isFinal ? $this->addFlag(self::FLAG_FINAL) : $this->removeFlag(self::FLAG_FINAL);
    }

    /**
     * @return bool
     */
    public function isFinal(): bool
    {
        return (bool) ($this->flags & self::FLAG_FINAL);
    }
}
