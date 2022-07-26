<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPStan\Rules\PhpDoc;

/** Requires "@property" annotations to be alphabetized. */
final class SortedPropertyAnnotationsRule extends AbstractSortedAnnotationsRule
{
    protected function targetTag(): string
    {
        return '@property';
    }
}
