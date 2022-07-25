<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\Domain\Core\Cleaner\CleanerInterface;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/** @internal */
final class CleanCommand extends AbstractCommand
{
    private const NAME = 'clean';

    private CleanerInterface $cleaner;

    public function __construct(CleanerInterface $cleaner, PathFactoryInterface $pathFactory)
    {
        parent::__construct(self::NAME, $pathFactory);

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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->confirm($input, $output)) {
            return self::FAILURE;
        }

        // ---------------------------------------------------------------------
        // (!) Here is the substance of the example. Invoke the Composer Stager
        //     API; be sure to catch the appropriate exceptions.
        // ---------------------------------------------------------------------
        try {
            $this->cleaner->clean(
                $this->getActiveDir(),
                $this->getStagingDir(),
                new ProcessOutputCallback($input, $output),
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
    private function confirm(InputInterface $input, OutputInterface $output): bool
    {
        $noInteraction = $input->getOption('no-interaction');
        assert(is_bool($noInteraction));

        if ($noInteraction) {
            return true;
        }

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);
        $question = new ConfirmationQuestion(
            'You are about to permanently remove the staging directory. This action cannot be undone. Continue? [Y/n] ',
        );

        return (bool) $helper->ask($input, $output, $question);
    }
}
