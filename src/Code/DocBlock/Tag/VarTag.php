<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code\DocBlock\Tag;

/**
 * Code is largely lifted from the Zend\Code\Generator\DocBlock\Tag\VarTag implementation in
 * Zend Code, released with the copyright and license below. It is modified to work with PHP AST.
 *
 * @see       https://github.com/zendframework/zend-code for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-code/blob/master/LICENSE.md New BSD License
 */
final class VarTag extends AbstractTypeableTag
{
    public function getName(): string
    {
        return 'var';
    }

    public function generate(): string
    {
        return '@var'
            . ((! empty($this->types)) ? ' ' . $this->getTypesAsString() : '')
            . ((! empty($this->description)) ? ' ' . $this->description : '');
    }
}
