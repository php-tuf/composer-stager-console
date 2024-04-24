<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Output;

use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface as SymfonyInputInterface;
use Symfony\Component\Console\Output\OutputInterface as SymfonyOutputInterface;

/** @internal */
final class ProcessOutputCallback implements OutputCallbackInterface
{
    /** @var array<string> */
    private array $errorOutput = [];

    /** @var array<string> */
    private array $output = [];

    public function __construct(
        private readonly SymfonyInputInterface $symfonyInput,
        private readonly SymfonyOutputInterface $symfonyOutput,
    ) {
    }

    public function clearErrorOutput(): void
    {
        $this->errorOutput = [];
    }

    public function clearOutput(): void
    {
        $this->output = [];
    }

    public function getErrorOutput(): array
    {
        return $this->errorOutput;
    }

    public function getOutput(): array
    {
        return $this->output;
    }

    /** @return array<string> */
    private function normalizeBuffer(string $buffer): array
    {
        // Convert Windows line endings.
        $buffer = str_replace("\r\n", "\n", $buffer);

        // Trim meaningless new lines at the beginning and end of buffers.
        $buffer = preg_replace("/(^\r?\n|\r?\n$)/", '', $buffer, 1);
        assert(is_string($buffer));

        // Split multiline strings into an array.
        return explode("\n", $buffer);
    }

    public function __invoke(OutputTypeEnum $type, string $buffer): void
    {
        $lines = $this->normalizeBuffer($buffer);

        if ($type === OutputTypeEnum::OUT) {
            $this->output = array_merge($this->output, $lines);
        } else {
            $this->errorOutput = array_merge($this->errorOutput, $lines);
        }

        try {
            if ($this->symfonyInput->getOption('quiet') === true) {
                return;
            }
        } catch (InvalidArgumentException) {
            // The interface allows no exceptions.
            return;
        }

        // Write process output as it comes.
        $this->symfonyOutput->write($buffer);
    }
}
