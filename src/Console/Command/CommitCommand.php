<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\Console\Output\ProcessOutputCallback;
use PhpTuf\ComposerStager\Domain\CommitterInterface;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStagerConsole\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/** @internal */
final class CommitCommand extends AbstractCommand
{
    private const NAME = 'commit';

    /** @var \PhpTuf\ComposerStager\Domain\CommitterInterface */
    private $committer;

    public function __construct(CommitterInterface $committer)
    {
        parent::__construct(self::NAME);

        $this->committer = $committer;
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Makes the staged changes live by syncing the active directory with the staging directory',
        );
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $activeDir = $input->getOption(Application::ACTIVE_DIR_OPTION);
        assert(is_string($activeDir));
        $stagingDir = $input->getOption(Application::STAGING_DIR_OPTION);
        assert(is_string($stagingDir));

        if (!$this->committer->directoryExists($stagingDir)) {
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
            $this->committer->commit(
                $stagingDir,
                $activeDir,
                null,
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
            'You are about to make the staged changes live. This action cannot be undone. Continue? [Y/n] ',
        );

        return (bool) $helper->ask($input, $output, $question);
    }
}
