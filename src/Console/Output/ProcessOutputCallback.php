<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Output;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
final class ProcessOutputCallback implements OutputCallbackInterface
{
    /** @var \Symfony\Component\Console\Output\OutputInterface */
    private $output;

    /** @var \Symfony\Component\Console\Input\InputInterface */
    private $input;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
    }

    /** @throws \Symfony\Component\Console\Exception\InvalidArgumentException */
    public function __invoke(string $type, string $buffer): void
    {
        if ($this->input->getOption('quiet') === true) {
            return;
        }

        // Write process output as it comes.
        $this->output->write($buffer);
    }
}
