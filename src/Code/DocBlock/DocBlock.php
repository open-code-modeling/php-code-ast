<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\Code\DocBlock;

use OpenCodeModeling\CodeAst\Code\DocBlock\Tag\Tag;

final class DocBlock
{
    /**
     * @var string
     */
    protected ?string $comment = null;

    /**
     * @var Tag[]
     */
    protected array $tags;

    public function __construct(?string $comment, Tag ...$tags)
    {
        $this->setComment($comment);
        $this->tags = $tags;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function addTag(Tag ...$tags): self
    {
        foreach ($tags as $tag) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function generate(): string
    {
        $comment = "/**\n";

        if ($this->comment) {
            $comment .= ' * ' . \trim(\preg_replace("/\n/", "\n * ", $this->comment)) . "\n *\n";
        }

        foreach ($this->tags as $tag) {
            $comment .= ' * ' . $tag->generate() . "\n";
        }

        $comment = \preg_replace("/ \* \n/", " *\n", $comment);

        if (\count($this->tags) === 0) {
            return \trim($comment) . '/';
        }

        return $comment . "\n */";
    }
}
