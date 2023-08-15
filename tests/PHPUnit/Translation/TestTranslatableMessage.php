<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Translation;

use PhpTuf\ComposerStager\API\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/** @phpcs:disable SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal */
class TestTranslatableMessage implements TranslatableInterface
{
    public function __construct(private readonly string $message = '')
    {
    }

    public function trans(?TranslatorInterface $translator = null, ?string $locale = null): string
    {
        return $this->message;
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
