<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\Console\Output\ProcessOutputCallback;
use PhpTuf\ComposerStager\Domain\BeginnerInterface;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\ExceptionInterface;
use PhpTuf\ComposerStagerConsole\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
final class BeginCommand extends AbstractCommand
{
    private const NAME = 'begin';

    /** @var \PhpTuf\ComposerStager\Domain\BeginnerInterface */
    private $beginner;

    public function __construct(BeginnerInterface $beginner)
    {
        parent::__construct(self::NAME);

        $this->beginner = $beginner;
    }

    protected function configure(): void
    {
        $this->setDescription('Begins the staging process by copying the active directory to the staging directory');
    }

    /** @throws \Symfony\Component\Console\Exception\InvalidArgumentException */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $activeDir = $input->getOption(Application::ACTIVE_DIR_OPTION);
        assert(is_string($activeDir));
        $stagingDir = $input->getOption(Application::STAGING_DIR_OPTION);
        assert(is_string($stagingDir));

        // ---------------------------------------------------------------------
        // (!) Here is the substance of the example. Invoke the Composer Stager
        //     API; be sure to catch the appropriate exceptions.
        // ---------------------------------------------------------------------
        try {
            $this->beginner->begin(
                $activeDir,
                $stagingDir,
                null,
                new ProcessOutputCallback($input, $output),
            );

            return self::SUCCESS;
        } catch (DirectoryAlreadyExistsException $e) {
            // Error-handling specifics may differ for your application. This
            // example outputs errors to the terminal.
            $output->writeln("<error>{$e->getMessage()}</error>");
            $output->writeln('Hint: Use the "clean" command to remove the staging directory');

            return self::FAILURE;
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        }
    }
}
