<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPStan\Rules\PhpDoc;

/** Requires "@see" annotations to be sorted alphabetically. */
final class SortedSeeAnnotationsRule extends AbstractSortedAnnotationsRule
{
    protected function targetTag(): string
    {
        return '@see';
    }
}
