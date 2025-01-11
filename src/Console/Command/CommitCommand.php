<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/** @internal */
final class CommitCommand extends AbstractCommand
{
    private const NAME = 'commit';

    public function __construct(private readonly CommitterInterface $committer, PathFactoryInterface $pathFactory, PathListFactoryInterface $pathListFactory)
    {
        parent::__construct(self::NAME, $pathFactory, $pathListFactory);
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Makes the staged changes live by syncing the active directory with the staging directory',
        );
    }

    /** @throws \Symfony\Component\Console\Exception\LogicException */
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
            $this->committer->commit(
                $this->getStagingDir(),
                $this->getActiveDir(),
	            $this->getExclusions(),
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

    /** @throws \Symfony\Component\Console\Exception\LogicException */
    private function confirm(InputInterface $input, OutputInterface $output): bool
    {
        try {
            $noInteraction = $input->getOption('no-interaction');
            assert(is_bool($noInteraction));
        } catch (InvalidArgumentException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }

        if ($noInteraction) {
            return true;
        }

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);
        $question = new ConfirmationQuestion(
            'You are about to make the staged changes live. This action cannot be undone. Continue? [Y/n] ',
        );

        try {
            return (bool) $helper->ask($input, $output, $question);
        } catch (RuntimeException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
