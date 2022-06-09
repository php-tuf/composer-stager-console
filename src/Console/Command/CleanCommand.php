<?php

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStagerConsole\Console\Application;
use PhpTuf\ComposerStager\Console\Output\ProcessOutputCallback;
use PhpTuf\ComposerStager\Domain\CleanerInterface;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @internal
 */
final class CleanCommand extends AbstractCommand
{
    private const NAME = 'clean';

    /**
     * @var \PhpTuf\ComposerStager\Domain\CleanerInterface
     */
    private $cleaner;

    public function __construct(CleanerInterface $cleaner)
    {
        parent::__construct(self::NAME);
        $this->cleaner = $cleaner;
    }

    protected function configure(): void
    {
        $this->setDescription('Removes the staging directory');
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $stagingDir */
        $stagingDir = $input->getOption(Application::STAGING_DIR_OPTION);

        if (!$this->cleaner->directoryExists($stagingDir)) {
            $output->writeln(sprintf('<error>The staging directory does not exist at "%s"</error>', $stagingDir));
            return self::FAILURE;
        }

        if (!$this->confirm($input, $output)) {
            return self::FAILURE;
        }

        // ---------------------------------------------------------------------
        // (!) Here is the substance of the example. Invoke the Composer Stager
        //     API; be sure to catch the appropriate exceptions.
        // ---------------------------------------------------------------------
        try {
            $this->cleaner->clean(
                $stagingDir,
                new ProcessOutputCallback($input, $output)
            );

            return self::SUCCESS;
        } catch (ExceptionInterface $e) {
            // Error-handling specifics may differ for your application. This
            // example outputs errors to the terminal.
            $output->writeln("<error>{$e->getMessage()}</error>");
            return self::FAILURE;
        }
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     */
    public function confirm(InputInterface $input, OutputInterface $output): bool
    {
        /** @var bool $noInteraction */
        $noInteraction = $input->getOption('no-interaction');
        if ($noInteraction) {
            return true;
        }

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('You are about to permanently remove the staging directory. This action cannot be undone. Continue? [Y/n] ');
        return (bool) $helper->ask($input, $output, $question);
    }
}
