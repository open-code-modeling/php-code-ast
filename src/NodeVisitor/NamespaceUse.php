<?php

/**
 * @see       https://github.com/open-code-modeling/php-code-ast for the canonical source repository
 * @copyright https://github.com/open-code-modeling/php-code-ast/blob/master/COPYRIGHT.md
 * @license   https://github.com/open-code-modeling/php-code-ast/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace OpenCodeModeling\CodeAst\NodeVisitor;

use PhpParser\BuilderFactory;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

class NamespaceUse extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $imports;

    /**
     * @var BuilderFactory
     **/
    private $builderFactory;

    public function __construct(...$imports)
    {
        $this->imports = $imports;
        $this->builderFactory = new BuilderFactory();
    }

    public function afterTraverse(array $nodes): ?array
    {
        $imports = $this->filterImports($nodes);

        if (\count($imports) === 0) {
            return null;
        }

        $newNodes = [];

        foreach ($nodes as $node) {
            $newNodes[] = $node;

            if ($node instanceof Stmt\Namespace_) {
                $stmts = $node->stmts;
                foreach ($imports as $import) {
                    if (\is_array($import)) {
                        $useNamespace = $this->builderFactory->use($import[0]);
                        $useNamespace->as($import[1]);
                    } else {
                        $useNamespace = $this->builderFactory->use($import);
                    }

                    \array_unshift($stmts, $useNamespace->getNode());
                }
                $node->stmts = $stmts; // @phpstan-ignore-line
            }
        }

        return $newNodes;
    }

    private function filterImports(array $nodes): array
    {
        $imports = $this->imports;

        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Stmt\Use_) {
                        $imports = \array_filter($imports, static function ($import) use ($stmt) {
                            $name = $import;

                            if (\is_array($import)) {
                                $name = $import[0];
                            }

                            return $name !== (string) $stmt->uses[0]->name;
                        });
                    }
                }
            } elseif ($node instanceof Stmt\Use_) {
                $imports = \array_filter($imports, static function ($import) use ($node) {
                    $name = $import;

                    if (\is_array($import)) {
                        $name = $import[0];
                    }

                    return $name === (string) $node->uses[0]->name;
                });
            }
        }

        return $imports;
    }
}
