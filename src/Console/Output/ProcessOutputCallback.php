<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Output;

use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Value\OutputTypeEnum;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
final class ProcessOutputCallback implements OutputCallbackInterface
{
    public function __construct(private readonly InputInterface $input, private readonly OutputInterface $output)
    {
    }

    public function __invoke(OutputTypeEnum $type, string $buffer): void
    {
        try {
            if ($this->input->getOption('quiet') === true) {
                return;
            }
        } catch (InvalidArgumentException) {
            // The interface allows no exceptions.
            return;
        }

        // Write process output as it comes.
        $this->output->write($buffer);
    }
}
