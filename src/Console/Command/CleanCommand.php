<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\API\Core\CleanerInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/** @internal */
final class CleanCommand extends AbstractCommand
{
    private const NAME = 'clean';

    public function __construct(private readonly CleanerInterface $cleaner, PathFactoryInterface $pathFactory)
    {
        parent::__construct(self::NAME, $pathFactory);
    }

    protected function configure(): void
    {
        $this->setDescription('Removes the staging directory');
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

    /** @throws \Symfony\Component\Console\Exception\LogicException */
    private function confirm(InputInterface $input, OutputInterface $output): bool
    {
        try {
            $noInteraction = $input->getOption('no-interaction');
        } catch (InvalidArgumentException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }

        assert(is_bool($noInteraction));

        if ($noInteraction) {
            return true;
        }

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);
        $question = new ConfirmationQuestion(
            'You are about to permanently remove the staging directory. This action cannot be undone. Continue? [Y/n] ',
        );

        try {
            return (bool) $helper->ask($input, $output, $question);
        } catch (RuntimeException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
