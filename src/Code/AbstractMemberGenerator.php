<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code;

use PhpParser\Node\Stmt\Class_;

/**
 * Code is largely lifted from the Zend\Code\Generator\AbstractMemberGenerator implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
abstract class AbstractMemberGenerator implements StatementGenerator
{
    /**#@+
     * @const int Flags for construction usage
     */
    public const FLAG_ABSTRACT = Class_::MODIFIER_ABSTRACT;
    public const FLAG_FINAL = Class_::MODIFIER_FINAL;
    public const FLAG_STATIC = Class_::MODIFIER_STATIC;
    public const FLAG_PUBLIC = Class_::MODIFIER_PUBLIC;
    public const FLAG_PROTECTED = Class_::MODIFIER_PROTECTED;
    public const FLAG_PRIVATE = Class_::MODIFIER_PRIVATE;
    /**#@-*/

    /**#@+
     * @param const string
     */
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PROTECTED = 'protected';
    public const VISIBILITY_PRIVATE = 'private';
    /**#@-*/

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $flags = self::FLAG_PUBLIC;

    /**
     * @param  int|array $flags
     * @return AbstractMemberGenerator
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
     * @return AbstractMemberGenerator
     */
    public function addFlag($flag): self
    {
        $this->setFlags($this->flags | $flag);

        return $this;
    }

    /**
     * @param  int $flag
     * @return AbstractMemberGenerator
     */
    public function removeFlag($flag): self
    {
        $this->setFlags($this->flags & ~$flag);

        return $this;
    }

    /**
     * @param  bool $isAbstract
     * @return AbstractMemberGenerator
     */
    public function setAbstract($isAbstract)
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
     * @return AbstractMemberGenerator
     */
    public function setFinal($isFinal)
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

    /**
     * @param  bool $isStatic
     * @return AbstractMemberGenerator
     */
    public function setStatic($isStatic)
    {
        return $isStatic ? $this->addFlag(self::FLAG_STATIC) : $this->removeFlag(self::FLAG_STATIC);
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return (bool) ($this->flags & self::FLAG_STATIC); // is FLAG_STATIC in flags
    }

    /**
     * @param  string $visibility
     * @return AbstractMemberGenerator
     */
    public function setVisibility($visibility)
    {
        switch ($visibility) {
            case self::VISIBILITY_PUBLIC:
                $this->removeFlag(self::FLAG_PRIVATE | self::FLAG_PROTECTED); // remove both
                $this->addFlag(self::FLAG_PUBLIC);
                break;
            case self::VISIBILITY_PROTECTED:
                $this->removeFlag(self::FLAG_PUBLIC | self::FLAG_PRIVATE); // remove both
                $this->addFlag(self::FLAG_PROTECTED);
                break;
            case self::VISIBILITY_PRIVATE:
                $this->removeFlag(self::FLAG_PUBLIC | self::FLAG_PROTECTED); // remove both
                $this->addFlag(self::FLAG_PRIVATE);
                break;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getVisibility(): string
    {
        switch (true) {
            case $this->flags & self::FLAG_PROTECTED:
                return self::VISIBILITY_PROTECTED;
            case $this->flags & self::FLAG_PRIVATE:
                return self::VISIBILITY_PRIVATE;
            default:
                return self::VISIBILITY_PUBLIC;
        }
    }

    /**
     * @param  string $name
     * @return AbstractMemberGenerator
     */
    public function setName($name): self
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
